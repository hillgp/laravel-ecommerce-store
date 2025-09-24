<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    /**
     * Nome da tabela
     */
    protected $table = 'customers';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'document',
        'birth_date',
        'gender',
        'is_active',
        'email_verified',
        'email_verified_at',
        'verification_token',
        'last_login_at',
        'avatar',
        'preferences',
        'meta_data',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'password',
        'verification_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'is_active' => 'boolean',
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'birth_date' => 'date',
        'preferences' => 'array',
        'meta_data' => 'array',
    ];

    /**
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->verification_token)) {
                $customer->verification_token = \Illuminate\Support\Str::random(64);
            }
        });
    }

    /**
     * Define o campo de senha para criptografia automática
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Pedidos do cliente
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Endereços do cliente
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    /**
     * Endereços de cobrança
     */
    public function billingAddresses(): HasMany
    {
        return $this->addresses()->where('type', 'billing');
    }

    /**
     * Endereços de entrega
     */
    public function shippingAddresses(): HasMany
    {
        return $this->addresses()->where('type', 'shipping');
    }

    /**
     * Endereço padrão de cobrança
     */
    public function defaultBillingAddress(): HasMany
    {
        return $this->billingAddresses()->where('is_default', true);
    }

    /**
     * Endereço padrão de entrega
     */
    public function defaultShippingAddress(): HasMany
    {
        return $this->shippingAddresses()->where('is_default', true);
    }

    /**
     * Carrinhos do cliente
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class, 'customer_id');
    }

    /**
     * Avaliações do cliente
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'customer_id');
    }

    /**
     * Wishlist do cliente
     */
    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'customer_id');
    }

    /**
     * Scope para clientes ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para clientes com email verificado
     */
    public function scopeVerified($query)
    {
        return $query->where('email_verified', true);
    }

    /**
     * Scope para clientes não verificados
     */
    public function scopeUnverified($query)
    {
        return $query->where('email_verified', false);
    }

    /**
     * Scope para busca por nome ou email
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('document', 'like', "%{$search}%");
        });
    }

    /**
     * Verifica se o cliente tem pedidos
     */
    public function hasOrders(): bool
    {
        return $this->orders()->exists();
    }

    /**
     * Verifica se o cliente tem endereços
     */
    public function hasAddresses(): bool
    {
        return $this->addresses()->exists();
    }

    /**
     * Obtém o endereço padrão de cobrança
     */
    public function getDefaultBillingAddress()
    {
        return $this->defaultBillingAddress()->first() ?? $this->billingAddresses()->first();
    }

    /**
     * Obtém o endereço padrão de entrega
     */
    public function getDefaultShippingAddress()
    {
        return $this->defaultShippingAddress()->first() ?? $this->shippingAddresses()->first();
    }

    /**
     * Define um endereço como padrão
     */
    public function setDefaultAddress(int $addressId): bool
    {
        $address = $this->addresses()->find($addressId);

        if (!$address) {
            return false;
        }

        // Remove todos os endereços padrão do mesmo tipo
        $this->addresses()
            ->where('type', $address->type)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Define o novo endereço como padrão
        $address->update(['is_default' => true]);

        return true;
    }

    /**
     * Adiciona um endereço
     */
    public function addAddress(array $data): CustomerAddress
    {
        // Se for o primeiro endereço ou marcado como padrão
        if ($this->addresses()->count() === 0 || ($data['is_default'] ?? false)) {
            $data['is_default'] = true;

            // Remove outros endereços padrão do mesmo tipo
            $this->addresses()
                ->where('type', $data['type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        return $this->addresses()->create($data);
    }

    /**
     * Verifica se o email está verificado
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified;
    }

    /**
     * Verifica o token de verificação
     */
    public function verifyEmail(string $token): bool
    {
        if ($this->verification_token !== $token) {
            return false;
        }

        $this->update([
            'email_verified' => true,
            'email_verified_at' => now(),
            'verification_token' => null,
        ]);

        return true;
    }

    /**
     * Regenera o token de verificação
     */
    public function regenerateVerificationToken(): string
    {
        $token = \Illuminate\Support\Str::random(64);

        $this->update([
            'verification_token' => $token,
            'email_verified' => false,
            'email_verified_at' => null,
        ]);

        return $token;
    }

    /**
     * Atualiza o último login
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Obtém a URL do avatar
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/customers/' . $this->avatar);
        }

        // Retorna avatar padrão baseado no gênero ou genérico
        $gender = strtolower($this->gender);
        if (in_array($gender, ['male', 'masculino', 'm'])) {
            return asset('images/avatars/default-male.png');
        } elseif (in_array($gender, ['female', 'feminino', 'f'])) {
            return asset('images/avatars/default-female.png');
        }

        return asset('images/avatars/default.png');
    }

    /**
     * Obtém a idade do cliente
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    /**
     * Obtém o nome do gênero
     */
    public function getGenderNameAttribute(): string
    {
        $genders = [
            'male' => 'Masculino',
            'female' => 'Feminino',
            'other' => 'Outro',
            'masculino' => 'Masculino',
            'feminino' => 'Feminino',
            'outro' => 'Outro',
        ];

        return $genders[strtolower($this->gender)] ?? 'Não informado';
    }

    /**
     * Obtém o documento formatado
     */
    public function getFormattedDocumentAttribute(): string
    {
        if (!$this->document) {
            return '';
        }

        // Formata CPF
        if (strlen($this->document) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->document);
        }

        // Formata CNPJ
        if (strlen($this->document) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $this->document);
        }

        return $this->document;
    }

    /**
     * Obtém o telefone formatado
     */
    public function getFormattedPhoneAttribute(): string
    {
        if (!$this->phone) {
            return '';
        }

        // Remove todos os caracteres não numéricos
        $phone = preg_replace('/\D/', '', $this->phone);

        // Formata telefone celular
        if (strlen($phone) === 11) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
        }

        // Formata telefone fixo
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 4) . '-' . substr($phone, 6);
        }

        return $this->phone;
    }

    /**
     * Obtém estatísticas do cliente
     */
    public function getStatsAttribute(): array
    {
        return [
            'total_orders' => $this->orders()->count(),
            'total_spent' => $this->orders()->sum('total'),
            'average_order_value' => $this->orders()->avg('total'),
            'last_order_date' => $this->orders()->latest()->first()?->created_at,
            'total_addresses' => $this->addresses()->count(),
            'total_reviews' => $this->reviews()->count(),
            'is_active' => $this->is_active,
            'is_verified' => $this->email_verified,
            'has_avatar' => !empty($this->avatar),
            'member_since' => $this->created_at,
        ];
    }

    /**
     * Obtém preferências do cliente
     */
    public function getPreference(string $key, $default = null)
    {
        return $this->preferences[$key] ?? $default;
    }

    /**
     * Define uma preferência
     */
    public function setPreference(string $key, $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Remove uma preferência
     */
    public function removePreference(string $key): void
    {
        $preferences = $this->preferences ?? [];
        unset($preferences[$key]);
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Obtém metadados
     */
    public function getMeta(string $key)
    {
        return $this->meta_data[$key] ?? null;
    }

    /**
     * Define metadados
     */
    public function setMeta(string $key, $value): void
    {
        $metaData = $this->meta_data ?? [];
        $metaData[$key] = $value;
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Remove metadados
     */
    public function removeMeta(string $key): void
    {
        $metaData = $this->meta_data ?? [];
        unset($metaData[$key]);
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Obtém pedidos recentes
     */
    public function getRecentOrders(int $limit = 5)
    {
        return $this->orders()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém produtos favoritos (baseado em pedidos)
     */
    public function getFavoriteProducts(int $limit = 10)
    {
        return Product::whereHas('orders', function ($query) {
            $query->where('customer_id', $this->id);
        })
        ->withCount(['orders as order_count' => function ($query) {
            $query->where('customer_id', $this->id);
        }])
        ->orderByDesc('order_count')
        ->limit($limit)
        ->get();
    }

    /**
     * Verifica se o cliente pode fazer login
     */
    public function canLogin(): bool
    {
        return $this->is_active && $this->email_verified;
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'formatted_phone' => $this->formatted_phone,
            'document' => $this->document,
            'formatted_document' => $this->formatted_document,
            'birth_date' => $this->birth_date,
            'age' => $this->age,
            'gender' => $this->gender,
            'gender_name' => $this->gender_name,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'is_verified' => $this->email_verified,
            'last_login_at' => $this->last_login_at,
            'stats' => $this->stats,
            'preferences' => $this->preferences,
        ];
    }
}