<?php

namespace App\Traits;

use App\Models\Certificate\Certificate;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasValidityDate
{
    protected function validityDate(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->term) {
                Certificate::TERM_INT => $this->jobRequestCertificate->int_cert_expires_on,
                Certificate::TERM_COND => $this->jobRequestCertificate->cond_cert_expires_on,
                Certificate::TERM_ST => $this->jobRequestCertificate->short_cert_expires_on,
                Certificate::TERM_FT => $this->jobRequestCertificate->full_cert_expires_on,
                default => null,
            }
        );
    }

    public function getIsValidAttribute(): bool
    {
        if (!$this->is_issued) {
            return false;
        }

        if (!$this->is_marked_valid) {
            return false;
        }

        $validityDate = $this->validityDate;
        return $validityDate ? $validityDate->isFuture() || $validityDate->isToday() : false;
    }
}
