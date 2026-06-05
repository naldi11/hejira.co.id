import { useForm, usePage } from '@inertiajs/react';
import Label from '@/Components/form/Label';
import Input from '@/Components/form/input/InputField';
import Button from '@/Components/ui/button/Button';
import InputError from '@/Components/InputError';

const route = window.route;

export default function UpdateProfileInformationForm({ className = '' }) {
    const user = usePage().props.auth.user;
    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: user.name,
        email: user.email,
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('profile.update'));
    };

    return (
        <section className={className}>
            <header className="mb-6">
                <h2 className="text-lg font-bold text-gray-800 dark:text-white/90">Informasi Profil</h2>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Perbarui nama dan alamat email akun Anda.</p>
            </header>

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <Label htmlFor="name">Nama</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                        placeholder="Nama Lengkap"
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        placeholder="Alamat Email"
                    />
                    <InputError message={errors.email} className="mt-2" />
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
