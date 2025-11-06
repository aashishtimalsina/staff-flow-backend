# Laravel Models Implementation Guide

## Table of Contents

- [User Model](#user-model)
- [Candidate Model](#candidate-model)
- [Client Model](#client-model)
- [BookingRequest Model](#bookingrequest-model)
- [Assignment Model](#assignment-model)
- [Timesheet Model](#timesheet-model)
- [Invoice Model](#invoice-model)
- [RateCard Model](#ratecard-model)
- [Model Traits](#model-traits)

---

## User Model

**File:** `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'uid',
        'name',
        'email',
        'password',
        'avatar_url',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Auto-generate UID on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->uid)) {
                $user->uid = 'user_' . Str::random(20);
            }
        });
    }

    // Role check methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isRecruiter(): bool
    {
        return $this->role === 'recruiter';
    }

    public function isFinance(): bool
    {
        return $this->role === 'finance';
    }

    public function isCompliance(): bool
    {
        return $this->role === 'compliance';
    }

    public function isWorker(): bool
    {
        return $this->role === 'worker';
    }

    // Permission check methods
    public function canAccessAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageUsers(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function canManageBookings(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'recruiter']);
    }

    public function canManageFinance(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'finance']);
    }

    public function canManageCompliance(): bool
    {
        return in_array($this->role, ['superadmin', 'admin', 'compliance']);
    }

    // Relationships
    public function candidate()
    {
        return $this->hasOne(Candidate::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdBookings()
    {
        return $this->hasMany(BookingRequest::class, 'created_by');
    }

    public function approvedTimesheets()
    {
        return $this->hasMany(Timesheet::class, 'approved_by');
    }
}
```

---

## Candidate Model

**File:** `app/Models/Candidate.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_role_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'location',
        'home_location',
        'skills',
        'availability',
        'status',
    ];

    protected $casts = [
        'skills' => 'array',
        'availability' => 'array',
        'dob' => 'date',
    ];

    protected $appends = ['full_name'];

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Check if candidate is available on a specific date
    public function isAvailableOn(string $date): bool
    {
        if (empty($this->availability)) {
            return false;
        }

        return in_array($date, $this->availability);
    }

    // Check if candidate is compliant
    public function isCompliant(): bool
    {
        $requiredDocs = $this->jobRole->complianceDocuments()->count();
        $approvedDocs = $this->complianceDocuments()
            ->where('status', 'Approved')
            ->where(function($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->count();

        return $requiredDocs === $approvedDocs && $requiredDocs > 0;
    }

    // Get compliance percentage
    public function getCompliancePercentage(): int
    {
        $requiredDocs = $this->jobRole->complianceDocuments()->count();

        if ($requiredDocs === 0) {
            return 100;
        }

        $approvedDocs = $this->complianceDocuments()
            ->where('status', 'Approved')
            ->where(function($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->count();

        return (int) (($approvedDocs / $requiredDocs) * 100);
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    public function complianceDocuments()
    {
        return $this->hasMany(CandidateCompliance::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeCompliant($query)
    {
        return $query->whereHas('complianceDocuments', function($q) {
            $q->where('status', 'Approved')
                ->where(function($subQ) {
                    $subQ->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>', now());
                });
        });
    }

    public function scopeForJobRole($query, int $jobRoleId)
    {
        return $query->where('job_role_id', $jobRoleId);
    }

    public function scopeAvailableOn($query, string $date)
    {
        return $query->whereJsonContains('availability', $date);
    }
}
```

---

## Client Model

**File:** `app/Models/Client.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'primary_contact',
        'account_manager_contact',
        'finance_contact',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Get active rate card for specific job role and date
    public function getApplicableRateCard(int $jobRoleId, string $date)
    {
        return $this->rateCards()
            ->where('job_role_id', $jobRoleId)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->first();
    }

    // Relationships
    public function rateCards()
    {
        return $this->hasMany(RateCard::class);
    }

    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function interviews()
    {
        return $this->hasMany(Interview::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

---

## BookingRequest Model

**File:** `app/Models/BookingRequest.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'job_role_id',
        'shift_start_time',
        'shift_end_time',
        'location',
        'candidates_needed',
        'status',
        'pay_rate',
        'bill_rate',
        'notes',
        'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'shift_start_time' => 'datetime',
        'shift_end_time' => 'datetime',
        'pay_rate' => 'decimal:2',
        'bill_rate' => 'decimal:2',
        'candidates_needed' => 'integer',
    ];

    // Determine work type based on shift start time
    public function getWorkType(): string
    {
        $start = Carbon::parse($this->shift_start_time);
        $dayOfWeek = $start->dayOfWeek;
        $hour = $start->hour;

        // Check if it's a UK bank holiday
        if ($this->isUKBankHoliday($start)) {
            return 'Bank Holiday';
        }

        // Weekend check (Saturday = 6, Sunday = 0)
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            return 'Weekend';
        }

        // Night shift check (6 PM to 6 AM)
        if ($hour >= 18 || $hour < 6) {
            return 'Night';
        }

        return 'Day';
    }

    // Check if date is UK bank holiday
    private function isUKBankHoliday(Carbon $date): bool
    {
        // Implement UK bank holiday logic
        // You can use an API or maintain a table of bank holidays
        $bankHolidays = [
            '2024-01-01', // New Year's Day
            '2024-12-25', // Christmas Day
            '2024-12-26', // Boxing Day
            // Add more as needed
        ];

        return in_array($date->format('Y-m-d'), $bankHolidays);
    }

    // Calculate shift duration in hours
    public function getShiftDurationHours(): float
    {
        $start = Carbon::parse($this->shift_start_time);
        $end = Carbon::parse($this->shift_end_time);

        return $start->diffInHours($end, true);
    }

    // Check if booking can be assigned
    public function canBeAssigned(): bool
    {
        return $this->status === 'Open' &&
               $this->assignments()->count() < $this->candidates_needed;
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'Open');
    }

    public function scopeBooked($query)
    {
        return $query->where('status', 'Booked');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('shift_start_time', '>', now());
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForJobRole($query, int $jobRoleId)
    {
        return $query->where('job_role_id', $jobRoleId);
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('shift_start_time', [$startDate, $endDate]);
    }
}
```

---

## Assignment Model

**File:** `app/Models/Assignment.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_request_id',
        'candidate_id',
        'status',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-create timesheet when assignment is created
        static::created(function ($assignment) {
            $assignment->createTimesheet();
        });

        // Update booking status when assignment is created
        static::created(function ($assignment) {
            $booking = $assignment->bookingRequest;

            if ($booking->assignments()->count() >= $booking->candidates_needed) {
                $booking->update(['status' => 'Booked']);
            }
        });
    }

    // Create timesheet for this assignment
    public function createTimesheet(): Timesheet
    {
        $timesheetNumber = 'TS-' . str_pad(
            Timesheet::count() + 1,
            5,
            '0',
            STR_PAD_LEFT
        );

        return $this->timesheet()->create([
            'timesheet_number' => $timesheetNumber,
            'status' => 'Draft',
            'hours_standard' => 0,
            'hours_overtime' => 0,
            'breaks' => 0,
        ]);
    }

    // Relationships
    public function bookingRequest()
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timesheet()
    {
        return $this->hasOne(Timesheet::class);
    }

    // Scopes
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'Confirmed');
    }

    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->where('candidate_id', $candidateId);
    }
}
```

---

## Timesheet Model

**File:** `app/Models/Timesheet.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'timesheet_number',
        'assignment_id',
        'hours_standard',
        'hours_overtime',
        'breaks',
        'expenses',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'hours_standard' => 'decimal:2',
        'hours_overtime' => 'decimal:2',
        'breaks' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Calculate total hours
    public function getTotalHours(): float
    {
        return $this->hours_standard + $this->hours_overtime - $this->breaks;
    }

    // Calculate pay amount
    public function calculatePay(): float
    {
        $booking = $this->assignment->bookingRequest;
        $standardPay = $this->hours_standard * $booking->pay_rate;
        $overtimePay = $this->hours_overtime * ($booking->pay_rate * 1.5); // 1.5x for overtime

        return $standardPay + $overtimePay;
    }

    // Calculate bill amount
    public function calculateBill(): float
    {
        $booking = $this->assignment->bookingRequest;
        $standardBill = $this->hours_standard * $booking->bill_rate;
        $overtimeBill = $this->hours_overtime * ($booking->bill_rate * 1.5);

        return $standardBill + $overtimeBill;
    }

    // Submit timesheet
    public function submit(): bool
    {
        if ($this->status !== 'Draft') {
            return false;
        }

        $this->update(['status' => 'Submitted']);

        // Send notification to finance team
        $this->notifyFinanceTeam();

        return true;
    }

    // Approve timesheet
    public function approve(User $approver): bool
    {
        if ($this->status !== 'Submitted') {
            return false;
        }

        $this->update([
            'status' => 'Approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Send notification to worker
        $this->notifyWorker('approved');

        return true;
    }

    // Reject timesheet
    public function reject(User $approver, string $reason): bool
    {
        if ($this->status !== 'Submitted') {
            return false;
        }

        $this->update([
            'status' => 'Rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Send notification to worker
        $this->notifyWorker('rejected');

        return true;
    }

    // Notification methods
    private function notifyFinanceTeam()
    {
        $financeUsers = User::where('role', 'finance')->get();

        foreach ($financeUsers as $user) {
            Notification::create([
                'user_id' => $user->id,
                'message' => "New timesheet {$this->timesheet_number} submitted for approval",
                'link' => "/finance/timesheets/{$this->id}",
            ]);
        }
    }

    private function notifyWorker(string $status)
    {
        $worker = $this->assignment->candidate->user;

        Notification::create([
            'user_id' => $worker->id,
            'message' => "Your timesheet {$this->timesheet_number} has been {$status}",
            'link' => "/timesheets/{$this->id}",
        ]);
    }

    // Relationships
    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'Submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    public function scopeForCandidate($query, int $candidateId)
    {
        return $query->whereHas('assignment', function($q) use ($candidateId) {
            $q->where('candidate_id', $candidateId);
        });
    }
}
```

---

## Invoice Model

**File:** `app/Models/Invoice.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'period_start',
        'period_end',
        'subtotal',
        'tax',
        'total',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate invoice number
        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $year = date('Y');
                $count = static::whereYear('created_at', $year)->count() + 1;
                $invoice->invoice_number = 'INV-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // Calculate totals from line items
    public function calculateTotals(float $taxRate = 20.0): void
    {
        $subtotal = $this->lineItems->sum('total');
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }

    // Mark as sent
    public function markAsSent(): void
    {
        $this->update(['status' => 'Sent']);
    }

    // Mark as paid
    public function markAsPaid(): void
    {
        $this->update(['status' => 'Paid']);
    }

    // Check if overdue
    public function isOverdue(): bool
    {
        if ($this->status === 'Paid' || $this->status === 'Cancelled') {
            return false;
        }

        // Assuming 30 days payment term
        $dueDate = $this->created_at->addDays(30);

        return now()->greaterThan($dueDate);
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'Draft');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'Sent');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'Paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'Paid')
            ->where('status', '!=', 'Cancelled')
            ->where('created_at', '<', now()->subDays(30));
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }
}
```

---

## RateCard Model

**File:** `app/Models/RateCard.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'job_role_id',
        'effective_date',
        'day_pay_rate',
        'day_bill_rate',
        'night_pay_rate',
        'night_bill_rate',
        'weekend_pay_rate',
        'weekend_bill_rate',
        'bank_holiday_pay_rate',
        'bank_holiday_bill_rate',
    ];

    protected $casts = [
        'effective_date' => 'datetime',
        'day_pay_rate' => 'decimal:2',
        'day_bill_rate' => 'decimal:2',
        'night_pay_rate' => 'decimal:2',
        'night_bill_rate' => 'decimal:2',
        'weekend_pay_rate' => 'decimal:2',
        'weekend_bill_rate' => 'decimal:2',
        'bank_holiday_pay_rate' => 'decimal:2',
        'bank_holiday_bill_rate' => 'decimal:2',
    ];

    // Get rates for specific work type
    public function getRatesForWorkType(string $workType): array
    {
        $field = strtolower(str_replace(' ', '_', $workType));

        return [
            'pay_rate' => $this->{$field . '_pay_rate'},
            'bill_rate' => $this->{$field . '_bill_rate'},
        ];
    }

    // Format for API response (matches frontend structure)
    public function toApiFormat(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'job_role_id' => $this->job_role_id,
            'effective_date' => $this->effective_date->toISOString(),
            'rates' => [
                'Day' => [
                    'pay_rate' => (float) $this->day_pay_rate,
                    'bill_rate' => (float) $this->day_bill_rate,
                ],
                'Night' => [
                    'pay_rate' => (float) $this->night_pay_rate,
                    'bill_rate' => (float) $this->night_bill_rate,
                ],
                'Weekend' => [
                    'pay_rate' => (float) $this->weekend_pay_rate,
                    'bill_rate' => (float) $this->weekend_bill_rate,
                ],
                'Bank Holiday' => [
                    'pay_rate' => (float) $this->bank_holiday_pay_rate,
                    'bill_rate' => (float) $this->bank_holiday_bill_rate,
                ],
            ],
        ];
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function jobRole()
    {
        return $this->belongsTo(JobRole::class);
    }

    // Scopes
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForJobRole($query, int $jobRoleId)
    {
        return $query->where('job_role_id', $jobRoleId);
    }

    public function scopeEffectiveOn($query, string $date)
    {
        return $query->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('effective_date', 'desc');
    }
}
```

---

## Model Traits

### Auditable Trait

**File:** `app/Traits/Auditable.php`

```php
<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditLog('create');
        });

        static::updated(function ($model) {
            $model->auditLog('update');
        });

        static::deleted(function ($model) {
            $model->auditLog('delete');
        });
    }

    public function auditLog(string $action, string $details = null)
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        AuditLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => $action,
            'entity' => $this->getTable(),
            'entity_id' => $this->id,
            'details' => $details ?? json_encode($this->getChanges()),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Usage in Models

```php
<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use Auditable; // Add this trait to enable auto-auditing

    // ... rest of the model code
}
```

---

## Additional Helper Models

### JobRole Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRole extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    public function complianceDocuments()
    {
        return $this->hasMany(ComplianceDocument::class);
    }

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }

    public function bookingRequests()
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function rateCards()
    {
        return $this->hasMany(RateCard::class);
    }
}
```

### Notification Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'link',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
```

---

## Testing Examples

### Candidate Model Test

**File:** `tests/Unit/Models/CandidateTest.php`

```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\JobRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_check_if_candidate_is_available_on_a_date()
    {
        $candidate = Candidate::factory()->create([
            'availability' => ['2024-12-01', '2024-12-02', '2024-12-03']
        ]);

        $this->assertTrue($candidate->isAvailableOn('2024-12-01'));
        $this->assertFalse($candidate->isAvailableOn('2024-12-05'));
    }

    /** @test */
    public function it_can_calculate_full_name()
    {
        $candidate = Candidate::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);

        $this->assertEquals('John Doe', $candidate->full_name);
    }

    /** @test */
    public function it_can_determine_compliance_status()
    {
        $jobRole = JobRole::factory()
            ->hasComplianceDocuments(3)
            ->create();

        $candidate = Candidate::factory()
            ->for($jobRole)
            ->create();

        // Create approved compliance documents
        $candidate->complianceDocuments()->create([
            'compliance_document_id' => $jobRole->complianceDocuments->first()->id,
            'document_name' => 'DBS',
            'status' => 'Approved',
            'expiry_date' => now()->addYear(),
        ]);

        $this->assertFalse($candidate->isCompliant());
        $this->assertEquals(33, $candidate->getCompliancePercentage());
    }
}
```

---

This comprehensive model guide provides everything you need to implement the Laravel models with proper relationships, methods, scopes, and traits. Each model includes business logic that matches your Firebase frontend implementation.
