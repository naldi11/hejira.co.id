// Material Symbols are bundled locally by app.css, including filled variants.
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
