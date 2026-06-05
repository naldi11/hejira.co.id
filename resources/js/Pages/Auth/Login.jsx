import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import Icon from '@/Components/Icon';

const route = window.route;

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    const fieldClass = 'w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-sm text-gray-800 dark:text-white outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 transition-all';

    return (
        <GuestLayout>
            <Head title="Masuk" />

            <div className="w-full max-w-md mx-auto pt-6 pb-10">
                <div className="mb-6 text-center sm:text-left">
                    <h1 className="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">Masuk</h1>
                    <p className="text-sm text-gray-500 dark:text-gray-400">Silakan masuk ke akun Anda.</p>
                </div>

                {status && <div className="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700">{status}</div>}

                <form onSubmit={submit}>
                    <div className="space-y-5">
                        {/* Email */}
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Email <span className="text-red-500">*</span>
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="Masukkan email Anda"
                                autoComplete="email"
                                className={fieldClass}
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        {/* Password */}
                        <div>
                            <label className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Password <span className="text-red-500">*</span>
                            </label>
                            <div className="relative">
                                <input
                                    id="password"
                                    type={showPassword ? 'text' : 'password'}
                                    name="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Masukkan password Anda"
                                    autoComplete="current-password"
                                    className={fieldClass}
                                />
                                <span
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2"
                                >
                                    <Icon
                                        name={showPassword ? 'visibility' : 'visibility_off'}
                                        className="text-gray-400 text-[20px]"
                                    />
                                </span>
                            </div>
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        {/* Remember + Lupa password */}
                        <div className="flex items-center justify-between">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                                />
                                <span className="text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
                            </label>

                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400"
                                >
                                    Lupa password?
                                </Link>
                            )}
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-lg bg-brand-500 px-4 py-3 text-sm font-bold text-white hover:bg-brand-600 disabled:opacity-60 transition-colors"
                        >
                            {processing ? 'Memproses...' : 'Masuk'}
                        </button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
