<?php

namespace App\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum FieldType: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case NUMBER = 'number';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case SELECT = 'select';
    case RADIO = 'radio';
    case CHECKBOX = 'checkbox';
    case FILE = 'file';
    case DATE = 'date';
    case GEO_COORDINATE = 'geo_coordinate';
    case ADDRESS = 'address';

    public const typesWithOptions = [
        self::SELECT,
        self::RADIO,
        self::CHECKBOX,
    ];

    public const typesWithFileUpload = [
        self::FILE,
    ];

    public const typesWithNumericValidation = [
        self::NUMBER,
    ];

    public const typesWithLengthValidation = [
        self::TEXT,
        self::TEXTAREA,
        self::EMAIL,
        self::PHONE,
    ];

    public function supportsOptions(): bool
    {
        return in_array($this, self::typesWithOptions, true);
    }

    public function supportsFileUpload(): bool
    {
        return in_array($this, self::typesWithFileUpload, true);
    }

    public function supportsNumericValidation(): bool
    {
        return in_array($this, self::typesWithNumericValidation, true);
    }

    public function supportsLengthValidation(): bool
    {
        return in_array($this, self::typesWithLengthValidation, true);
    }

    public function requiresGeoCoordinate(): bool
    {
        return match ($this) {
            self::GEO_COORDINATE => true,
            default => false,
        };
    }
}
