import { useState, useEffect } from 'react';
import Modal from '@/Components/Modal';
import Icon from '@/Components/Icon';
import axios from 'axios';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableSelect from '@/Components/SearchableSelect';

export function FilterTransactionsModal({ show, onClose, filters, onApply, entity }) {
    const [startDate, setStartDate] = useState(filters.start_date || filters.date || '');
    const [endDate, setEndDate] = useState(filters.end_date || filters.date || '');
    const [shiftId, setShiftId] = useState(filters.shift_id || '');
    const [availableShifts, setAvailableShifts] = useState([]);
    const [loadingShifts, setLoadingShifts] = useState(false);

    useEffect(() => {
        if (startDate && startDate === endDate) {
            setLoadingShifts(true);
            axios.get(`/${entity}/shifts/by-date`, { params: { date: startDate, entity: entity } })
                .then(res => {
                    setAvailableShifts(res.data || []);
                })
                .catch(err => console.error(err))
                .finally(() => setLoadingShifts(false));
        } else {
            setAvailableShifts([]);
            setShiftId('');
        }
    }, [startDate, endDate, entity]);

    const handleApply = (e) => {
        e.preventDefault();
        onApply({ start_date: startDate, end_date: endDate, shift_id: shiftId });
    };

    const handleReset = () => {
        setStartDate('');
        setEndDate('');
        setShiftId('');
        onApply({ start_date: '', end_date: '', shift_id: '' });
    };

    const shiftOptions = availableShifts.map(shift => ({
        value: shift.id,
        label: shift.name,
        sublabel: shift.user ? shift.user.name : ''
    }));

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={handleApply} className="p-6">
                <div className="flex justify-between items-center mb-5">
                    <h2 className="text-lg font-bold text-gray-900 dark:text-white">Filter Transaksi</h2>
                </div>

                <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Dari Tanggal" />
                            <TextInput 
                                type="date" 
                                className="mt-1 block w-full" 
                                value={startDate} 
                                onChange={e => setStartDate(e.target.value)} 
                            />
                        </div>
                        <div>
                            <InputLabel value="Sampai Tanggal" />
                            <TextInput 
                                type="date" 
                                className="mt-1 block w-full" 
                                value={endDate} 
                                onChange={e => setEndDate(e.target.value)} 
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Pilih Shift" />
                        <SearchableSelect 
                            options={shiftOptions}
                            value={shiftId}
                            onChange={setShiftId}
                            disabled={!(startDate && startDate === endDate) || loadingShifts}
                            placeholder={!(startDate && startDate === endDate) ? 'Pilih tanggal yang sama di kolom Dari & Sampai' : (loadingShifts ? 'Memuat...' : 'Semua Shift')}
                            accentColor="orange"
                        />
                        <p className="mt-1 text-xs text-gray-500">Hanya bisa dipilih jika rentang filter tepat 1 hari.</p>
                    </div>
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={handleReset}>
                        Reset
                    </SecondaryButton>
                    <PrimaryButton type="submit">
                        Terapkan Filter
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
