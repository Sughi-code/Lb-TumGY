<?php

class CustomerModel
{
    public function __construct(
        public ?int $customer_id = null,
        public ?int $store_id = null,
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $email = null,
        public ?int $address_id = null,
        public ?int $active = null,
        public ?string $create_date = null,
        public ?string $last_update = null
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            customer_id: $data['customer_id'] ?? null,
            store_id: $data['store_id'] ?? null,
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            email: $data['email'] ?? null,
            address_id: $data['address_id'] ?? null,
            active: $data['active'] ?? null,
            create_date: $data['create_date'] ?? null,
            last_update: $data['last_update'] ?? null
        );
    }
    
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'store_id' => $this->store_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'address_id' => $this->address_id,
            'active' => $this->active,
            'create_date' => $this->create_date,
            'last_update' => $this->last_update
        ];
    }
}

class FilmModel
{
    public function __construct(
        public ?int $film_id = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $release_year = null,
        public ?int $language_id = null,
        public ?int $rental_duration = null,
        public ?float $rental_rate = null,
        public ?int $length = null,
        public ?float $replacement_cost = null,
        public ?string $rating = null,
        public ?string $special_features = null,
        public ?string $last_update = null
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            film_id: $data['film_id'] ?? null,
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            release_year: $data['release_year'] ?? null,
            language_id: $data['language_id'] ?? null,
            rental_duration: $data['rental_duration'] ?? null,
            rental_rate: $data['rental_rate'] ?? null,
            length: $data['length'] ?? null,
            replacement_cost: $data['replacement_cost'] ?? null,
            rating: $data['rating'] ?? null,
            special_features: $data['special_features'] ?? null,
            last_update: $data['last_update'] ?? null
        );
    }
    
    public function toArray(): array
    {
        return [
            'film_id' => $this->film_id,
            'title' => $this->title,
            'description' => $this->description,
            'release_year' => $this->release_year,
            'language_id' => $this->language_id,
            'rental_duration' => $this->rental_duration,
            'rental_rate' => $this->rental_rate,
            'length' => $this->length,
            'replacement_cost' => $this->replacement_cost,
            'rating' => $this->rating,
            'special_features' => $this->special_features,
            'last_update' => $this->last_update
        ];
    }
}

class RentalModel
{
    public function __construct(
        public ?int $rental_id = null,
        public ?string $rental_date = null,
        public ?int $inventory_id = null,
        public ?int $customer_id = null,
        public ?string $return_date = null,
        public ?int $staff_id = null,
        public ?string $last_update = null
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            rental_id: $data['rental_id'] ?? null,
            rental_date: $data['rental_date'] ?? null,
            inventory_id: $data['inventory_id'] ?? null,
            customer_id: $data['customer_id'] ?? null,
            return_date: $data['return_date'] ?? null,
            staff_id: $data['staff_id'] ?? null,
            last_update: $data['last_update'] ?? null
        );
    }
    
    public function toArray(): array
    {
        return [
            'rental_id' => $this->rental_id,
            'rental_date' => $this->rental_date,
            'inventory_id' => $this->inventory_id,
            'customer_id' => $this->customer_id,
            'return_date' => $this->return_date,
            'staff_id' => $this->staff_id,
            'last_update' => $this->last_update
        ];
    }
}
?>