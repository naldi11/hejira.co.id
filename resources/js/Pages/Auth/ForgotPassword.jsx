import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

const route = window.route;

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Lupa Password" />

            <h1 className="mb-1 text-xl font-black text-slate-800">Lupa Password</h1>
            <p className="mb-6 text-sm text-slate-500">Masukkan email Anda, kami kirim tautan untuk reset password.</p>

            {status && <div className="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700">{status}</div>}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput id="email" type="email" name="email" value={data.email} className="mt-1 block w-full" isFocused onChange={(e) => setData('email', e.target.value)} />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <PrimaryButton className="w-full" disabled={processing}>{processing ? 'Mengirim...' : 'Kirim Tautan Reset'}</PrimaryButton>

                <Link href={route('login')} className="block text-center text-sm text-indigo-600 hover:text-indigo-800">← Kembali ke Masuk</Link>
            </form>
        </GuestLayout>
    );
}
