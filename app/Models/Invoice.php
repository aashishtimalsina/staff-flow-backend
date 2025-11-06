<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'vat_amount',
        'total_amount',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Auto-generate invoice number on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber();
            }
        });
    }

    // Generate unique invoice number
    protected static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = Carbon::now()->format('Ymd');
        $lastInvoice = self::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Calculate totals from line items
    public function calculateTotals(): void
    {
        $subtotal = $this->lineItems()->sum('total');
        $this->subtotal = $subtotal;
        $this->vat_amount = $subtotal * 0.20; // 20% VAT
        $this->total_amount = $subtotal + $this->vat_amount;
    }

    // Check if invoice is overdue
    public function isOverdue(): bool
    {
        return $this->status !== 'Paid' &&
            $this->due_date &&
            Carbon::parse($this->due_date)->isPast();
    }

    // Check if invoice can be sent
    public function canBeSent(): bool
    {
        return $this->status === 'Draft' && $this->lineItems()->count() > 0;
    }

    // Mark as sent
    public function markAsSent(): bool
    {
        if (!$this->canBeSent()) {
            return false;
        }

        $this->status = 'Sent';
        return $this->save();
    }

    // Mark as paid
    public function markAsPaid(): bool
    {
        if (!in_array($this->status, ['Sent', 'Overdue'])) {
            return false;
        }

        $this->status = 'Paid';
        return $this->save();
    }

    // Add line item from timesheet
    public function addTimesheetLineItem(Timesheet $timesheet): InvoiceLineItem
    {
        $lineItem = new InvoiceLineItem([
            'timesheet_id' => $timesheet->id,
            'description' => "Timesheet {$timesheet->timesheet_number} - " .
                $timesheet->candidate->full_name . " - " .
                $timesheet->shift_start_time->format('d/m/Y'),
            'quantity' => $timesheet->hours_worked,
            'unit_price' => $timesheet->hourly_rate,
            'total' => $timesheet->total_amount,
        ]);

        $this->lineItems()->save($lineItem);
        $this->calculateTotals();
        $this->save();

        return $lineItem;
    }

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
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
            ->where('due_date', '<', Carbon::now());
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
