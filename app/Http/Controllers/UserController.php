<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->search($request->string('search')->toString())
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $rawEmail = trim((string) $request->input('email', ''));
        $normalizedEmail = function_exists('mb_strtolower')
            ? mb_strtolower($rawEmail, 'UTF-8')
            : strtolower($rawEmail);

        $request->merge(['email' => $normalizedEmail]);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(10)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR])],
        ], [], [
            'name' => 'نام',
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
            'phone' => 'تلفن',
            'role' => 'نقش',
        ]);

        $validatedData['is_active'] = true;

        User::create($validatedData);

        return redirect()->route('users.index')->with('success', 'کاربر جدید با موفقیت افزوده شد.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $rawEmail = trim((string) $request->input('email', ''));
        $normalizedEmail = function_exists('mb_strtolower')
            ? mb_strtolower($rawEmail, 'UTF-8')
            : strtolower($rawEmail);

        $request->merge(['email' => $normalizedEmail]);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed', Password::min(10)->letters()->numbers()],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR])],
        ], [], [
            'name' => 'نام',
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
            'phone' => 'تلفن',
            'role' => 'نقش',
        ]);

        if ($user->id === auth()->id() && $validatedData['role'] !== $user->role) {
            return back()->with('warning', 'نقش حساب خودتان را نمی‌توانید تغییر دهید.');
        }

        if (blank($validatedData['password'] ?? null)) {
            unset($validatedData['password']);
        }

        $user->update($validatedData);

        return redirect()->route('users.index')->with('success', 'اطلاعات کاربر بروزرسانی شد.');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('warning', 'نمی‌توانید حساب خودتان را غیرفعال کنید.');
        }

        if ($user->is_active && $this->isLastActiveAdmin($user)) {
            return back()->with('warning', 'حداقل یک ادمین فعال باید باقی بماند.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'کاربر فعال شد.' : 'کاربر غیرفعال شد.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('warning', 'نمی‌توانید حساب خودتان را حذف کنید.');
        }

        if ($this->isLastActiveAdmin($user)) {
            return back()->with('warning', 'حداقل یک ادمین فعال باید باقی بماند.');
        }

        $user->delete();

        return back()->with('success', 'کاربر حذف شد. (سوابق صورتحساب‌های او حفظ می‌شود)');
    }

    private function isLastActiveAdmin(User $user): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return User::where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->doesntExist();
    }
}
