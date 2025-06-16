<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailConfirmation extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string, mixed>
     */
    protected $fillable = [
        'submission_field_id',
        'email_address',
        'confirmation_token',
        'confirmed_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the submission field that owns this email confirmation.
     */
    public function submissionField(): BelongsTo
    {
        return $this->belongsTo(SubmissionField::class);
    }

    /**
     * Check if the email is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if the email is pending confirmation.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark the email as confirmed.
     */
    public function markAsConfirmed(): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Generate a new confirmation token.
     */
    public function generateToken(): string
    {
        $token = Str::random(64);
        $this->update(['confirmation_token' => $token]);
        return $token;
    }

    /**
     * Create a new email confirmation for a submission field.
     */
    public static function createForSubmissionField(SubmissionField $submissionField, string $emailAddress): self
    {
        return self::create([
            'submission_field_id' => $submissionField->id,
            'email_address' => $emailAddress,
            'confirmation_token' => Str::random(64),
            'status' => 'pending',
        ]);
    }

    /**
     * Find an email confirmation by token.
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('confirmation_token', $token)->first();
    }

    /**
     * Confirm an email by token.
     */
    public static function confirmByToken(string $token): bool
    {
        $confirmation = self::findByToken($token);

        if (!$confirmation || $confirmation->isConfirmed()) {
            return false;
        }

        $confirmation->markAsConfirmed();
        return true;
    }

    /**
     * Get the confirmation URL.
     */
    public function getConfirmationUrl(): string
    {
        return url("/email-confirmation/{$this->confirmation_token}");
    }
}
