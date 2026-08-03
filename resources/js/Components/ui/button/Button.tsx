import { ButtonHTMLAttributes, ReactNode } from "react";

interface ButtonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, "type"> {
  children: ReactNode; // Button text or content
  size?: "sm" | "md"; // Button size
  variant?: "primary" | "outline" | "secondary" | "danger"; // Button variant
  startIcon?: ReactNode; // Icon before the text
  endIcon?: ReactNode; // Icon after the text
  onClick?: () => void; // Click handler
  disabled?: boolean; // Disabled state
  className?: string; // Extra classes
  /**
   * PENTING: default-nya "button", bukan "submit".
   *
   * Sebelumnya prop ini tidak ada dan tidak pernah diteruskan ke <button>, sehingga
   * berlaku default HTML (type="submit"). Akibatnya tombol seperti "Batal" atau
   * "Tolak" yang berada di dalam <form> ikut men-submit form tersebut — mis. tombol
   * "Tolak Request" di Gudang/TransferRequests/Show justru menyetujui request, dan
   * "Batal" di form hapus akun justru menghapus akun.
   */
  type?: "button" | "submit" | "reset";
}

const Button: React.FC<ButtonProps> = ({
  children,
  size = "md",
  variant = "primary",
  startIcon,
  endIcon,
  onClick,
  className = "",
  disabled = false,
  type = "button",
  ...rest
}) => {
  // Size Classes
  const sizeClasses = {
    sm: "px-4 py-3 text-sm",
    md: "px-5 py-3.5 text-sm",
  };

  // Variant Classes
  const variantClasses = {
    primary:
      "bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600 disabled:bg-brand-300",
    outline:
      "bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300",
    secondary:
      "bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/[0.06] dark:text-gray-300 dark:hover:bg-white/[0.1]",
    danger:
      "bg-rose-500 text-white shadow-theme-xs hover:bg-rose-600 disabled:bg-rose-300",
  };

  // Fallback bila varian tak dikenal dikirim (mis. variant="secondary" di QrPrint):
  // dulu nilai tak dikenal menyisipkan string "undefined" ke className sehingga
  // tombol kehilangan seluruh gayanya.
  const variantClass = variantClasses[variant] ?? variantClasses.primary;

  return (
    <button
      type={type}
      className={`inline-flex items-center justify-center gap-2 rounded-lg transition ${className} ${
        sizeClasses[size] ?? sizeClasses.md
      } ${variantClass} ${disabled ? "cursor-not-allowed opacity-50" : ""}`}
      onClick={onClick}
      disabled={disabled}
      {...rest}
    >
      {startIcon && <span className="flex items-center">{startIcon}</span>}
      {children}
      {endIcon && <span className="flex items-center">{endIcon}</span>}
    </button>
  );
};

export default Button;
