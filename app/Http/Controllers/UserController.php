<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR])],
        ], [], [
            'name' => 'نام',
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
            'phone' => 'تلفن',
            'role' => 'نقش',
        ]);

        $data['is_active'] = true;

        User::create($data);

        return redirect()->route('users.index')->with('success', 'کاربر جدید با موفقیت افزوده شد.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR])],
        ], [], [
            'name' => 'نام',
            'email' => 'ایمیل',
            'password' => 'رمز عبور',
            'phone' => 'تلفن',
            'role' => 'نقش',
        ]);

        if ($user->id === auth()->id() && $data['role'] !== $user->role) {
            return back()->with('warning', 'نقش حساب خودتان را نمی‌توانید تغییر دهید.');
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

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
