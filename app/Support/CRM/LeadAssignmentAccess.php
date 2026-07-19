<?php

declare(strict_types=1);

namespace App\Support\CRM;

use App\Models\Employee;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

final class LeadAssignmentAccess
{
    public const MANAGER_ROLES = [
        'Admin',
        'Developer',
        'Sales Manager',
        'Manager',
        'General Manager',
        'Director',
        'Chief Executive Officer',
    ];

    public static function canManage(
        ?User $user,
    ): bool {
        return (bool) (
            $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(self::MANAGER_ROLES)
        );
    }

    public static function currentActiveEmployee(
        ?User $user,
    ): ?Employee {
        if (! $user) {
            return null;
        }

        return Employee::query()
            ->where('user_id', $user->getKey())
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first([
                'id',
                'user_id',
                'full_name',
            ]);
    }

    public static function currentActiveEmployeeId(
        ?User $user,
    ): ?int {
        $employee = self::currentActiveEmployee($user);

        return $employee
            ? (int) $employee->getKey()
            : null;
    }

    public static function scopeEmployeeOptions(
        Builder $query,
        ?User $user,
    ): Builder {
        $query
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if (self::canManage($user)) {
            return $query;
        }

        $employeeId =
            self::currentActiveEmployeeId($user);

        if (! $employeeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($employeeId);
    }

    public static function scopeLeadQuery(
        Builder $query,
        ?User $user,
    ): Builder {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (self::canManage($user)) {
            return $query;
        }

        $employeeId =
            self::currentActiveEmployeeId($user);

        if (! $employeeId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'assigned_employee_id',
            $employeeId,
        );
    }

    public static function canAccessLead(
        ?User $user,
        Lead $lead,
    ): bool {
        if (! $user) {
            return false;
        }

        if (self::canManage($user)) {
            return true;
        }

        $employeeId =
            self::currentActiveEmployeeId($user);

        return $employeeId !== null
            && (int) $lead->assigned_employee_id
                === $employeeId;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function enforceWriteAssignment(
        array $data,
        ?User $user,
    ): array {
        if (self::canManage($user)) {
            return self::enforceManagerAssignment(
                $data,
            );
        }

        $employee =
            self::currentActiveEmployee($user);

        if (! $employee) {
            throw ValidationException::withMessages([
                'assigned_employee_id' => [
                    'Your account must be linked to an active employee before creating or editing leads.',
                ],
            ]);
        }

        $employeeId =
            (int) $employee->getKey();

        if (
            array_key_exists(
                'assigned_employee_id',
                $data,
            )
        ) {
            $submitted =
                $data['assigned_employee_id'];

            if (
                $submitted === null
                || $submitted === ''
            ) {
                throw ValidationException::withMessages([
                    'assigned_employee_id' => [
                        'Lead assignment cannot be removed from your employee profile.',
                    ],
                ]);
            }

            $submittedId =
                self::parseEmployeeId($submitted);

            if ($submittedId !== $employeeId) {
                throw ValidationException::withMessages([
                    'assigned_employee_id' => [
                        'You may assign leads only to your own active employee profile.',
                    ],
                ]);
            }
        }

        $data['assigned_employee_id'] =
            $employeeId;

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function enforceManagerAssignment(
        array $data,
    ): array {
        if (
            ! array_key_exists(
                'assigned_employee_id',
                $data,
            )
        ) {
            return $data;
        }

        $submitted =
            $data['assigned_employee_id'];

        if (
            $submitted === null
            || $submitted === ''
        ) {
            $data['assigned_employee_id'] = null;

            return $data;
        }

        $employeeId =
            self::parseEmployeeId($submitted);

        $isActive = Employee::query()
            ->whereKey($employeeId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->exists();

        if (! $isActive) {
            throw ValidationException::withMessages([
                'assigned_employee_id' => [
                    'The selected employee must be active.',
                ],
            ]);
        }

        $data['assigned_employee_id'] =
            $employeeId;

        return $data;
    }

    private static function parseEmployeeId(
        mixed $value,
    ): int {
        $employeeId = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        if ($employeeId === false) {
            throw ValidationException::withMessages([
                'assigned_employee_id' => [
                    'The selected employee is invalid.',
                ],
            ]);
        }

        return $employeeId;
    }

    public static function helpText(
        ?User $user,
    ): string {
        if (self::canManage($user)) {
            return 'Choose an active sales employee or leave the lead unassigned.';
        }

        $employee =
            self::currentActiveEmployee($user);

        if (! $employee) {
            return 'No active employee is linked to your account. Lead creation and editing are blocked.';
        }

        return sprintf(
            'Assigned to your employee profile: %s.',
            $employee->full_name,
        );
    }
}