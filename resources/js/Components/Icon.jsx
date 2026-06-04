// Thin wrapper around Google Material Symbols so pages don't repeat the class string.
export default function Icon({ name, className = '', filled = false, ...props }) {
    return (
        <span
            className={`material-symbols-outlined${filled ? ' fill' : ''} ${className}`}
            aria-hidden="true"
            {...props}
        >
            {name}
        </span>
    );
}
