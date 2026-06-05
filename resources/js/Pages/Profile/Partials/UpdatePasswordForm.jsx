import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import Label from '@/Components/form/Label';
import Input from '@/Components/form/input/InputField';
import Button from '@/Components/ui/button/Button';
import InputError from '@/Components/InputError';

const route = window.route;

export default function UpdatePasswordForm({ className = '' }) {
    const passwordInput = useRef();
    const currentPasswordInput = useRef();

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (err) => {
                if (err.password) { reset('password', 'password_confirmation'); passwordInput.current?.focus(); }
                if (err.current_password) { reset('current_password'); currentPasswordInput.current?.focus(); }
            },
        });
    };

    return (
        <section className={className}>
            <header className="mb-6">
                <h2 className="text-lg font-bold text-gray-800 dark:text-white/90">Ubah Password</h2>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Gunakan password yang panjang dan acak agar akun tetap aman.</p>
            </header>

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <Label htmlFor="current_password">Password Saat Ini</Label>
                    <Input
                        id="current_password"
                        type="password"
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                        autoComplete="current-password"
                        placeholder="••••••••"
                    />
                    <InputError message={errors.current_password} className="mt-2" />
                </div>

                <div>
                    <Label htmlFor="password">Password Baru</Label>
                    <Input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        autoComplete="new-password"
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div>
                    <Label htmlFor="password_confirmation">Konfirmasi Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        autoComplete="new-password"
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="flex items-center gap-4 pt-2">
                    <Button disabled={processing} variant="primary" size="md">
                        Simpan
                    </Button>
                    {recentlySuccessful && (
                        <p className="text-sm text-success-600 dark:text-success-400 font-medium">Tersimpan.</p>
                    )}
                </div>
            </form>
        </section>
    );
}
