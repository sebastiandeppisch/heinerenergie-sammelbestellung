<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Setting extends Model
{
    protected $fillable = [
        'value',
        'key',
    ];

    protected $appends = ['type', 'name'];

    protected static function defaultConfig()
    {

        return [
            'impress' => [
                'name' => 'Impressum',
                'type' => 'text',
            ],
            'datapolicy' => [
                'name' => 'Datenschutzerklärung',
                'type' => 'text',
            ],
            'advisorInfo' => [
                'name' => 'Berater*innen Info',
                'type' => 'text',
            ],
            'newAdviceMail' => [
                'name' => 'Neue Beratung E-Mail',
                'type' => 'text',
            ],
            'defaultLogo' => [
                'name' => 'Standard Logo',
                'type' => 'image',
            ],
            'defaultFavicon' => [
                'name' => 'Standard Favicon',
                'type' => 'image',
            ],
        ];
    }

    public static function set(string $key, $value): void
    {
        if (! static::exists($key)) {
            throw new InvalidArgumentException("Key $key not found");
        }
        $setting = static::firstOrCreate(['key' => $key]);
        $setting->value = $value;
        $setting->save();
    }

    public static function get($key): mixed
    {
        if (! static::exists($key)) {
            throw new InvalidArgumentException("Key $key not found");
        }
        $setting = static::where('key', $key)->first();

        return $setting?->value;
    }

    public function getValueAttribute(): mixed
    {
        $value = $this->attributes['value'];
        settype($value, $this->nativeType());

        return $value;
    }

    public function setValueAttribute(mixed $value)
    {
        if ($value === null) {
            if (! $this->nullable()) {
                throw new InvalidArgumentException($this->key.' is not nullable');
            }
        } else {
            $type = gettype($value);
            if ($type !== $this->nativeType()) {
                throw new InvalidArgumentException("Invalid type: $type $value for ".$this->key);
            }
        }
        $this->attributes['value'] = $value;
    }

    private function config()
    {
        return static::defaultConfig()[$this->key];
    }

    private function getType()
    {
        if ($this->hasConfig()) {
            if (array_key_exists('type', $this->config())) {
                return $this->config()['type'];
            }

            return gettype($this->config()['default']);
        }

        return gettype($this->config());
    }

    private function nativeType()
    {
        if ($this->getType() === 'image') {
            return 'string';
        }
        if ($this->getType() === 'text') {
            return 'string';
        }
        return $this->getType();
    }

    private function nullable()
    {
        if ($this->hasConfig()) {
            $config = $this->config();
            if (array_key_exists('nullable', $config)) {
                return $config['nullable'];
            }
        }

        return false;
    }

    private function hasConfig()
    {
        return is_array($this->config());
    }

    public static function exists(string $key)
    {
        return array_key_exists($key, static::defaultConfig());
    }

    public function getNameAttribute(): string
    {
        $config = $this->config();
        if (is_array($config)) {
            return $config['name'];
        }

        return $config;
    }

    public function getTypeAttribute(): string
    {
        return $this->getType();
    }
}
