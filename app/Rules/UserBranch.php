<?php

namespace App\Rules;

use App\Models\Branch;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserBranch implements ValidationRule
{
    public function __construct(private mixed $entity) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($this->entity, ['hendhys', 'jihans'], true)) {
            $fail('Unit ini tidak memerlukan cabang. Kosongkan pilihan cabang.');

            return;
        }

        $branch = Branch::find($value);
        if (! $branch || ! $branch->is_active || $branch->entity !== $this->entity) {
            $fail('Pilih cabang aktif yang sesuai dengan unit usaha pengguna.');
        }
    }
}
