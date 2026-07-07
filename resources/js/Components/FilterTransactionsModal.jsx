import { useState, useEffect } from 'react';
import Modal from '@/Components/Modal';
import Icon from '@/Components/Icon';
import axios from 'axios';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export function FilterTransactionsModal({ show, onClose, filters, onApply, entity }) {
    const [startDate, setStartDate] = useState(filters.start_date || filters.date || '');
    const [endDate, setEndDate] = useState(filters.end_date || filters.date || '');
    const [shiftId, setShiftId] = useState(filters.shift_id || '');
    const [availableShifts, setAvailableShifts] = useState([]);
    const [loadingShifts, setLoadingShifts] = useState(false);

    useEffect(() => {
        if (startDate && startDate === endDate) {
            setLoadingShifts(true);
            axios.get(`/${entity}/shifts/by-date`, { params: { date: startDate } })
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

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={handleApply} className="p-6">
                <div className="flex justify-between items-center mb-5">
                    <h2 className="text-lg font-bold text-gray-900 dark:text-white">Filter Transaksi</h2>
                    <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-500">
                        <Icon name="close" className="text-xl" />
                    </button>
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

                    {startDate && startDate === endDate && (
                        <div>
                            <InputLabel value="Pilih Shift" />
                            <select 
                                className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-amber-500 dark:focus:border-amber-600 focus:ring-amber-500 dark:focus:ring-amber-600 rounded-md shadow-sm"
                                value={shiftId}
                                onChange={e => setShiftId(e.target.value)}
                                disabled={loadingShifts}
                            >
                                <option value="">{loadingShifts ? 'Memuat...' : 'Semua Shift'}</option>
                                {availableShifts.map(shift => (
                                    <option key={shift.id} value={shift.id}>{shift.name}</option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-gray-500">Hanya muncul jika rentang 1 hari.</p>
                        </div>
                    )}
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
