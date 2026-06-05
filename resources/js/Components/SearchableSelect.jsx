import { useState, useRef, useEffect, useCallback } from 'react';
import Icon from '@/Components/Icon';

/**
 * SearchableSelect — TomSelect/Select2-like searchable dropdown.
 * Uses fixed-position so it's never clipped by overflow:hidden tables/cards.
 *
 * Props:
 *   options     : Array<{ value, label, sublabel? }>
 *   value       : current selected value
 *   onChange    : (value) => void
 *   placeholder : string
 *   disabled    : bool
 *   accentColor : 'amber' | 'orange'
 *   className   : string (extra trigger classes)
 */
export default function SearchableSelect({
    options = [],
    value = '',
    onChange,
    placeholder = '-- Pilih --',
    disabled = false,
    accentColor = 'amber',
    className = '',
}) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [panelStyle, setPanelStyle] = useState({});
    const [highlighted, setHighlighted] = useState(-1);

    const triggerRef = useRef(null);
    const inputRef   = useRef(null);
    const listRef    = useRef(null);
    const panelRef   = useRef(null);

    const PANEL_MAX_H = 340; // max height of the entire dropdown panel (px)
    const SEARCH_H    = 80;  // approx height of search bar area

    const selectedOption = options.find(o => String(o.value) === String(value));

    const filtered = query.trim()
        ? options.filter(o =>
              o.label.toLowerCase().includes(query.toLowerCase()) ||
              (o.sublabel && o.sublabel.toLowerCase().includes(query.toLowerCase()))
          )
        : options;

    const accent = accentColor === 'orange'
        ? { ring: 'border-orange-500 ring-2 ring-orange-500/20', rowHover: 'bg-orange-50 dark:bg-orange-500/10', selText: 'text-orange-600 dark:text-orange-400', check: 'text-orange-500' }
        : { ring: 'border-amber-500 ring-2 ring-amber-500/20',  rowHover: 'bg-amber-50 dark:bg-amber-500/10',   selText: 'text-amber-600 dark:text-amber-400',   check: 'text-amber-500' };

    /* ── Calculate panel position ─────────────────────────────────────────── */
    const calcPosition = useCallback(() => {
        if (!triggerRef.current) return;
        const r          = triggerRef.current.getBoundingClientRect();
        const spaceBelow = window.innerHeight - r.bottom - 6;
        const spaceAbove = r.top - 6;

        // Always use `top` (never `bottom`) for predictable math
        if (spaceBelow >= 180 || spaceBelow >= spaceAbove) {
            // ▼ drop down
            const panelH = Math.min(PANEL_MAX_H, spaceBelow);
            setPanelStyle({
                position: 'fixed',
                top:       r.bottom + 4,
                left:      r.left,
                width:     r.width,
                maxHeight: panelH,
                zIndex:    99999,
            });
        } else {
            // ▲ drop up — top = trigger.top - panelHeight - gap
            const panelH = Math.min(PANEL_MAX_H, spaceAbove);
            setPanelStyle({
                position: 'fixed',
                top:       r.top - panelH - 4,
                left:      r.left,
                width:     r.width,
                maxHeight: panelH,
                zIndex:    99999,
            });
        }
    }, []);

    /* ── Open / close ─────────────────────────────────────────────────────── */
    const openPanel = () => {
        if (disabled) return;
        calcPosition();
        setOpen(true);
        setHighlighted(-1);
    };

    const closePanel = () => {
        setOpen(false);
        setQuery('');
        setHighlighted(-1);
    };

    /* ── Auto-focus search on open ────────────────────────────────────────── */
    useEffect(() => {
        if (open && inputRef.current) inputRef.current.focus();
    }, [open]);

    /* ── Re-position on scroll / resize ──────────────────────────────────── */
    useEffect(() => {
        if (!open) return;
        window.addEventListener('scroll', calcPosition, true);
        window.addEventListener('resize', calcPosition);
        return () => {
            window.removeEventListener('scroll', calcPosition, true);
            window.removeEventListener('resize', calcPosition);
        };
    }, [open, calcPosition]);

    /* ── Close on outside click ───────────────────────────────────────────── */
    useEffect(() => {
        if (!open) return;
        const onDown = (e) => {
            if (
                triggerRef.current && !triggerRef.current.contains(e.target) &&
                panelRef.current   && !panelRef.current.contains(e.target)
            ) closePanel();
        };
        document.addEventListener('mousedown', onDown);
        return () => document.removeEventListener('mousedown', onDown);
    }, [open]);

    /* ── Scroll highlighted row into view ────────────────────────────────── */
    useEffect(() => {
        if (listRef.current && highlighted >= 0) {
            const el = listRef.current.children[highlighted];
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    }, [highlighted]);

    /* ── Keyboard ─────────────────────────────────────────────────────────── */
    const onTriggerKey = (e) => {
        if (disabled) return;
        if (!open && ['Enter', ' ', 'ArrowDown'].includes(e.key)) {
            e.preventDefault();
            openPanel();
        }
    };

    const onSearchKey = (e) => {
        switch (e.key) {
            case 'ArrowDown': e.preventDefault(); setHighlighted(h => Math.min(h + 1, filtered.length - 1)); break;
            case 'ArrowUp':   e.preventDefault(); setHighlighted(h => Math.max(h - 1, 0)); break;
            case 'Enter':
                e.preventDefault();
                if (highlighted >= 0 && filtered[highlighted]) doSelect(filtered[highlighted]);
                break;
            case 'Escape': closePanel(); break;
        }
    };

    /* ── Interaction helpers ─────────────────────────────────────────────── */
    const doSelect = (opt) => { onChange(opt.value); closePanel(); };
    const doClear  = (e)   => { e.stopPropagation(); onChange(''); setQuery(''); };

    /* ── Render ─────────────────────────────────────────────────────────────*/
    const listMaxH = Math.max(60, (panelStyle.maxHeight ?? PANEL_MAX_H) - SEARCH_H);

    return (
        <>
            {/* ── Trigger button ── */}
            <div
                ref={triggerRef}
                role="combobox"
                aria-expanded={open}
                aria-haspopup="listbox"
                tabIndex={disabled ? -1 : 0}
                onClick={openPanel}
                onKeyDown={onTriggerKey}
                className={[
                    'relative flex w-full min-h-[46px] cursor-pointer select-none items-center justify-between rounded-lg border px-3.5 text-sm transition-all outline-none',
                    'bg-white dark:bg-white/[0.03]',
                    open
                        ? accent.ring
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600',
                    disabled ? 'cursor-not-allowed opacity-50' : '',
                    className,
                ].join(' ')}
            >
                <span className={`flex-1 truncate pr-2 text-[13px] leading-5 ${
                    selectedOption ? 'text-gray-800 dark:text-white/90' : 'text-gray-400 dark:text-gray-500'
                }`}>
                    {selectedOption ? (
                        <>
                            {selectedOption.label}
                            {selectedOption.sublabel && (
                                <span className="ml-2 text-[11px] text-gray-400 dark:text-gray-500">
                                    {selectedOption.sublabel}
                                </span>
                            )}
                        </>
                    ) : placeholder}
                </span>

                <span className="flex shrink-0 items-center gap-0.5">
                    {selectedOption && !disabled && (
                        <button
                            type="button" tabIndex={-1} onClick={doClear}
                            className="flex h-6 w-6 items-center justify-center rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <Icon name="close" className="text-[16px]" />
                        </button>
                    )}
                    <Icon
                        name={open ? 'keyboard_arrow_up' : 'keyboard_arrow_down'}
                        className="text-[22px] text-gray-400"
                    />
                </span>
            </div>

            {/* ── Dropdown panel — fixed so it escapes overflow:hidden ── */}
            {open && (
                <div
                    ref={panelRef}
                    style={panelStyle}
                    className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
                >
                    {/* Search input */}
                    <div className="border-b border-gray-100 px-3 py-2.5 dark:border-gray-800">
                        <div className="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800">
                            <Icon name="search" className="shrink-0 text-[20px] text-gray-400" />
                            <input
                                ref={inputRef}
                                type="text"
                                value={query}
                                onChange={(e) => { setQuery(e.target.value); setHighlighted(-1); }}
                                onKeyDown={onSearchKey}
                                placeholder="Ketik untuk mencari..."
                                className="w-full bg-transparent text-[13px] leading-5 outline-none text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                            />
                            {query && (
                                <button type="button" onClick={() => setQuery('')}
                                    className="shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <Icon name="close" className="text-[16px]" />
                                </button>
                            )}
                        </div>
                        <p className="mt-1 text-[11px] text-gray-400 dark:text-gray-500 px-0.5">
                            {filtered.length} dari {options.length} item
                        </p>
                    </div>

                    {/* Options list */}
                    <div
                        ref={listRef}
                        role="listbox"
                        className="overflow-y-auto"
                        style={{ maxHeight: listMaxH }}
                    >
                        {filtered.length === 0 ? (
                            <div className="px-4 py-8 text-center">
                                <Icon name="search_off" className="mx-auto mb-2 block text-[32px] text-gray-300 dark:text-gray-600" />
                                <p className="text-sm text-gray-400 dark:text-gray-500">Tidak ditemukan: <strong className="text-gray-600 dark:text-gray-400">"{query}"</strong></p>
                            </div>
                        ) : filtered.map((opt, idx) => {
                            const isSel  = String(opt.value) === String(value);
                            const isHigh = idx === highlighted;
                            return (
                                <div
                                    key={opt.value}
                                    role="option"
                                    aria-selected={isSel}
                                    className={[
                                        'flex cursor-pointer items-center justify-between border-b border-gray-50 px-4 py-2.5 transition-colors dark:border-gray-800/60',
                                        isHigh ? accent.rowHover : 'hover:bg-gray-50 dark:hover:bg-gray-800/60',
                                        isSel  ? `${accent.selText} font-semibold` : 'text-gray-700 dark:text-gray-200',
                                    ].join(' ')}
                                    onMouseDown={(e) => { e.preventDefault(); doSelect(opt); }}
                                    onMouseEnter={() => setHighlighted(idx)}
                                >
                                    <span className="flex flex-col">
                                        <span className="text-[13px] leading-5">{opt.label}</span>
                                        {opt.sublabel && (
                                            <span className="text-[11px] leading-4 text-gray-400 dark:text-gray-500">{opt.sublabel}</span>
                                        )}
                                    </span>
                                    {isSel && (
                                        <Icon name="check_circle" className={`shrink-0 text-[20px] ${accent.check}`} />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </>
    );
}
