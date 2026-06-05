import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import Modal from '@/Components/Modal';
import Label from '@/Components/form/Label';
import Input from '@/Components/form/input/InputField';
import Button from '@/Components/ui/button/Button';
import InputError from '@/Components/InputError';

const route = window.route;

export default function DeleteUserForm({ className = '' }) {
    const [confirming, setConfirming] = useState(false);
    const passwordInput = useRef();
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm({ password: '' });

    const close = () => { setConfirming(false); clearErrors(); reset(); };

    const submit = (e) => {
        e.preventDefault();
        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => close(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    return (
        <section className={className}>
            <header className="mb-6">
                <h2 className="text-lg font-bold text-error-600 dark:text-error-500">Hapus Akun</h2>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Setelah dihapus, semua data akun ini hilang permanen.</p>
            </header>

            <Button 
                onClick={() => setConfirming(true)}
                className="bg-error-600 text-white hover:bg-error-700 dark:bg-error-600/80 dark:hover:bg-error-700/80 shadow-theme-xs px-5 py-3 text-sm font-medium"
            >
                Hapus Akun
            </Button>

            <Modal show={confirming} onClose={close} title="Hapus Akun?" subtitle="Tindakan ini permanen" icon="warning" maxWidth="max-w-md">
                <form onSubmit={submit} className="space-y-5">
                    <p className="text-sm text-gray-500 dark:text-gray-400">Masukkan password untuk mengonfirmasi penghapusan akun secara permanen.</p>
                    <div>
                        <Label htmlFor="delete_password" className="sr-only">Password</Label>
                        <Input
                            id="delete_password"
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Password Konfirmasi"
                            required
                        />
                        <InputError message={errors.password} className="mt-2" />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <Button 
                            variant="outline" 
                            size="sm"
                            onClick={close}
                        >
                            Batal
                        </Button>
                        <Button 
                            disabled={processing}
                            className="bg-error-600 text-white hover:bg-error-700 dark:bg-error-600/80 dark:hover:bg-error-700/80 shadow-theme-xs px-4 py-3 text-sm"
                        >
                            Hapus Permanen
                        </Button>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
