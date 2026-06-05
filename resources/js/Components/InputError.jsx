export default function InputError({ message, className = '', ...props }) {
    if (!message) return null;
    return (
        <p {...props} className={`text-sm text-rose-600 ${className}`}>
            {message}
        </p>
    );
}
