<?php

namespace App\Services;

use App\Models\Billing;
use App\Models\Owner;
use App\Models\Representative;
use App\Models\Wagon;
use App\Models\WagonOwnerLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class WagonModificationService
{
    /**
     * اعمال ویرایش مشخصات واگن (شماره واگن، شماره خط و صاحب کالا)، ثبت لاگ و همگام‌سازی صورتحساب/درخواست بارگیری
     *
     * @param  array<string, mixed>  $validatedInput
     * @return array{message: string, billing: ?Billing}
     */
    public function modifyWagon(Wagon $wagon, array $validatedInput, ?int $actorUserId = null): array
    {
        $oldWagonNumber = trim((string) $wagon->wagon_number);
        $newWagonNumber = trim((string) ($validatedInput['wagon_number'] ?? ''));

        if ($newWagonNumber === '') {
            throw ValidationException::withMessages([
                'wagon_number' => 'وارد کردن شماره واگن معتبر الزامی است.',
            ]);
        }

        $oldOwnerId = $wagon->effectiveOwnerId();
        $oldOwner = $wagon->owner ?? $wagon->cargoRequest?->owner ?? ($oldOwnerId ? Owner::find($oldOwnerId) : null);
        $newOwner = ! empty($validatedInput['owner_id']) ? Owner::findOrFail($validatedInput['owner_id']) : null;
        $newOwnerId = $newOwner?->id;

        $oldLineNumber = trim((string) ($wagon->line_number ?? ''));
        $newLineNumber = array_key_exists('line_number', $validatedInput)
            ? trim((string) ($validatedInput['line_number'] ?? ''))
            : $oldLineNumber;
        $lineChanged = $newLineNumber !== $oldLineNumber;

        $numberChanged = $newWagonNumber !== $oldWagonNumber;
        $ownerChanged = (int) ($oldOwnerId ?? 0) !== (int) ($newOwnerId ?? 0);

        if (! $numberChanged && ! $ownerChanged && ! $lineChanged) {
            throw ValidationException::withMessages([
                'wagon_number' => 'هیچ تغییری در مشخصات واگن اعمال نشده است.',
            ]);
        }

        if ($numberChanged) {
            $duplicateInRequest = Wagon::where('cargo_request_id', $wagon->cargo_request_id)
                ->where('wagon_number', $newWagonNumber)
                ->where('id', '!=', $wagon->id)
                ->exists();

            if ($duplicateInRequest) {
                throw ValidationException::withMessages([
                    'wagon_number' => "شماره واگن «{$newWagonNumber}» قبلاً در همین درخواست بارگیری ثبت شده است.",
                ]);
            }
        }

        $note = trim((string) ($validatedInput['owner_change_note'] ?? ''));

        if ($note === '') {
            throw ValidationException::withMessages([
                'owner_change_note' => 'ثبت علت و توضیح برای ویرایش واگن الزامی است.',
            ]);
        }

        $billing = $wagon->billingItem?->billing;

        $changeType = match (true) {
            $numberChanged && $ownerChanged => 'both',
            $numberChanged => 'wagon_number',
            default => 'owner',
        };

        DB::transaction(function () use (
            $wagon,
            $oldWagonNumber,
            $newWagonNumber,
            $numberChanged,
            $newLineNumber,
            $lineChanged,
            $oldOwnerId,
            $oldOwner,
            $newOwner,
            $ownerChanged,
            $changeType,
            $note,
            $validatedInput,
            $billing,
            $actorUserId
        ) {
            $updatePayload = [
                'owner_change_note' => $note,
                'owner_changed_at' => now(),
                'owner_changed_by' => $actorUserId,
                'owner_change_count' => (int) $wagon->owner_change_count + 1,
            ];

            if ($lineChanged) {
                $updatePayload['line_number'] = $newLineNumber !== '' ? $newLineNumber : null;
            }

            if ($numberChanged) {
                $updatePayload['previous_wagon_number'] = $oldWagonNumber;
                $updatePayload['wagon_number'] = $newWagonNumber;
            }

            if ($ownerChanged) {
                $updatePayload['previous_owner_id'] = $oldOwnerId;
                $updatePayload['owner_id'] = $newOwner?->id;
            }

            $wagon->update($updatePayload);

            if ($newOwner || $oldOwnerId) {
                WagonOwnerLog::create([
                    'wagon_id' => $wagon->id,
                    'billing_id' => $billing?->id,
                    'change_type' => $changeType,
                    'old_wagon_number' => $oldWagonNumber,
                    'new_wagon_number' => $newWagonNumber,
                    'old_owner_id' => $oldOwnerId,
                    'new_owner_id' => $newOwner?->id ?? $oldOwnerId,
                    'old_owner_name' => $oldOwner?->name ?? 'ناشناس',
                    'new_owner_name' => $newOwner?->name ?? 'ناشناس',
                    'user_id' => $actorUserId,
                    'note' => $note,
                ]);
            }

            if ($ownerChanged && $newOwner) {
                $this->syncBillingAndCargoRequest($wagon, $newOwner, $billing, $validatedInput);
            }
        });

        $newOwnerLabel = $newOwner?->name ?? 'ناشناس';
        $message = match ($changeType) {
            'both' => "شماره واگن از «{$oldWagonNumber}» به «{$newWagonNumber}» و صاحب کالا به «{$newOwnerLabel}» با موفقیت ویرایش شد و با ذکر علت در داشبورد مدیر ثبت گردید.",
            'wagon_number' => "شماره واگن از «{$oldWagonNumber}» به «{$newWagonNumber}» با موفقیت اصلاح شد و با ذکر علت در داشبورد مدیر ثبت گردید.",
            default => "صاحب کالای واگن «{$wagon->wagon_number}» با موفقیت به «{$newOwnerLabel}» تغییر یافت و در آمار واگن‌های ویرایش‌شده ثبت شد.",
        };

        return [
            'message' => $message,
            'billing' => $billing,
        ];
    }

    /**
     * @param  array<string, mixed>  $validatedInput
     */
    private function syncBillingAndCargoRequest(
        Wagon $wagon,
        Owner $newOwner,
        ?Billing $billing,
        array $validatedInput
    ): void {
        if ($billing && ! $billing->isCancelled()) {
            $billing->load('items.wagon.cargoRequest');

            $allBillingWagonsSameOwner = $billing->items->isNotEmpty()
                && $billing->items->every(fn ($item) => (int) $item->wagon?->effectiveOwnerId() === (int) $newOwner->id);

            if ($allBillingWagonsSameOwner) {
                $newRep = null;

                if (! empty($validatedInput['representative_id'])) {
                    $newRep = Representative::find($validatedInput['representative_id']);
                } elseif ($billing->representative_id && DB::table('owner_representative')
                    ->where('owner_id', $newOwner->id)
                    ->where('representative_id', $billing->representative_id)
                    ->exists()) {
                    $newRep = $billing->representative;
                } else {
                    $newRep = $newOwner->representatives()->where('is_active', true)->orderBy('representatives.id')->first();
                }

                $billing->update([
                    'owner_id' => $newOwner->id,
                    'owner_name' => $newOwner->name,
                    'representative_id' => $newRep?->id,
                    'representative_name' => $newRep?->name,
                ]);
            }
        }

        $cargoRequest = $wagon->cargoRequest()->with('wagons')->first();

        if ($cargoRequest) {
            $allRequestWagonsSameOwner = $cargoRequest->wagons->isNotEmpty()
                && $cargoRequest->wagons->every(fn (Wagon $w) => (int) $w->effectiveOwnerId() === (int) $newOwner->id);

            if ($allRequestWagonsSameOwner) {
                $repId = $cargoRequest->representative_id;

                if (! empty($validatedInput['representative_id'])) {
                    $repId = (int) $validatedInput['representative_id'];
                } elseif (! DB::table('owner_representative')
                    ->where('owner_id', $newOwner->id)
                    ->where('representative_id', $repId)
                    ->exists()) {
                    $repId = $newOwner->representatives()
                        ->orderByDesc('is_active')
                        ->orderBy('representatives.id')
                        ->value('representatives.id') ?? $repId;
                }

                $cargoRequest->update([
                    'owner_id' => $newOwner->id,
                    'representative_id' => $repId,
                ]);
            }
        }
    }
}
