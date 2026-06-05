import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import Label from '@/components/form/Label';
import Input from '@/components/form/input/InputField';
import Checkbox from '@/components/form/input/Checkbox';
import Button from '@/components/ui/button/Button';
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
                    <div className="space-y-6">
                        <div>
                            <Label>Email <span className="text-error-500">*</span></Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="Masukkan email Anda"
                                error={!!errors.email}
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <Label>Password <span className="text-error-500">*</span></Label>
                            <div className="relative">
                                <Input
                                    id="password"
                                    type={showPassword ? "text" : "password"}
                                    name="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Masukkan password Anda"
                                    error={!!errors.password}
                                />
                                <span
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2"
                                >
                                    {showPassword ? (
                                        <Icon name="visibility" className="text-gray-500 text-[20px]" />
                                    ) : (
                                        <Icon name="visibility_off" className="text-gray-500 text-[20px]" />
                                    )}
                                </span>
                            </div>
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="remember"
                                    checked={data.remember}
                                    onChange={(checked) => setData('remember', checked)}
                                    label="Ingat saya"
                                />
                            </div>
                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400"
                                >
                                    Lupa password?
                                </Link>
                            )}
                        </div>

                        <div>
                            <Button
                                className="w-full"
                                size="sm"
                                disabled={processing}
                            >
                                {processing ? 'Memproses...' : 'Masuk'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
