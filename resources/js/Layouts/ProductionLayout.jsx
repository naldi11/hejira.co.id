import { usePage } from '@inertiajs/react';
import GudangLayout from './GudangLayout';
import HendhysLayout from './HendhysLayout';
import JihansLayout from './JihansLayout';

export default function ProductionLayout(props) {
    const { auth } = usePage().props;
    const Shell = auth?.user?.roles?.includes('super_admin')
        ? GudangLayout
        : auth?.user?.entity === 'jihans' ? JihansLayout : HendhysLayout;

    return <Shell {...props} />;
}
