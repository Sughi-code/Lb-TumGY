<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Модель арендатора
 */
class TenantModel
{
    /**
     * Конструктор модели арендатора
     * @param int|null $id Идентификатор записи
     * @param string|null $code Код арендатора
     * @param string|null $lastName Фамилия арендатора
     * @param string|null $createdAt Дата создания записи
     * @param string|null $updatedAt Дата обновления записи
     */
    public function __construct(
        public ?int $id = null,
        public ?string $code = null,
        public ?string $last_name = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}
    
    /**
     * Создание объекта из массива данных
     * @param array $data Массив данных
     * @return self Экземпляр класса
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            code: $data['code'] ?? null,
            last_name: $data['last_name'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null
        );
    }
}

/**
 * Модель недвижимости
 */
class RentalPropertyModel
{
    /**
     * Конструктор модели недвижимости
     * @param int|null $id Идентификатор записи
     * @param string|null $code Код недвижимости
     * @param string|null $type Тип недвижимости
     * @param float|null $monthly_rent Ежемесячная арендная плата
     * @param string|null $created_at Дата создания записи
     * @param string|null $updated_at Дата обновления записи
     */
    public function __construct(
        public ?int $id = null,
        public ?string $code = null,
        public ?string $type = null,
        public ?float $monthly_rent = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}
}

/**
 * Модель информации об аренде
 */
class RentalInfoModel
{
    /**
     * Конструктор модели информации об аренде
     * @param int|null $id Идентификатор записи
     * @param int|null $tenant_id ID арендатора
     * @param int|null $property_id ID недвижимости
     * @param string|null $start_date Дата начала аренды
     * @param int|null $lease_term Срок аренды (в месяцах)
     * @param string|null $created_at Дата создания записи
     * @param string|null $updated_at Дата обновления записи
     */
    public function __construct(
        public ?int $id = null,
        public ?int $tenant_id = null,
        public ?int $property_id = null,
        public ?string $start_date = null,
        public ?int $lease_term = null,
        public ?string $created_at = null,
        public ?string $updated_at = null
    ) {}
}

/**
 * Репозиторий для работы с данными аренды
 */
class RentalRepository
{
    /**
     * Конструктор репозитория
     * @param PDO $db Объект соединения с базой данных
     */
    public function __construct(private PDO $db) {}
    
    /**
     * Получение всех арендаторов
     * @return array Массив арендаторов
     */
    public function getAllTenants(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM tenants ORDER BY last_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Получение всех недвижимостей
     * @return array Массив недвижимостей
     */
    public function getAllProperties(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM rental_properties ORDER BY type");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Получение всех арендных информаций
     * @return array Массив арендных информаций
     */
    public function getAllRentalInfos(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM rental_info ORDER BY start_date DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Получение арендатора по идентификатору
     * @param int $id Идентификатор арендатора
     * @return array|null Данные арендатора или null
     */
    public function getTenantById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tenants WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Получение недвижимости по идентификатору
     * @param int $id Идентификатор недвижимости
     * @return array|null Данные недвижимости или null
     */
    public function getPropertyById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM rental_properties WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Получение арендной информации по идентификатору
     * @param int $id Идентификатор арендной информации
     * @return array|null Данные арендной информации или null
     */
    public function getRentalInfoById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM rental_info WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Создание нового арендатора
     * @param array $data Данные для создания
     * @return int Идентификатор созданной записи
     */
    public function createTenant(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO tenants (code, last_name) 
            VALUES (:code, :last_name)
        ");
        
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':last_name', $data['last_name']);
        
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    
    /**
     * Создание новой недвижимости
     * @param array $data Данные для создания
     * @return int Идентификатор созданной записи
     */
    public function createProperty(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO rental_properties (code, type, monthly_rent) 
            VALUES (:code, :type, :monthly_rent)
        ");
        
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':type', $data['type']);
        $stmt->bindValue(':monthly_rent', $data['monthly_rent'], PDO::PARAM_STR);
        
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    
    /**
     * Создание новой арендной информации
     * @param array $data Данные для создания
     * @return int Идентификатор созданной записи
     */
    public function createRentalInfo(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO rental_info (tenant_id, property_id, start_date, lease_term) 
            VALUES (:tenant_id, :property_id, :start_date, :lease_term)
        ");
        
        $stmt->bindValue(':tenant_id', $data['tenant_id'], PDO::PARAM_INT);
        $stmt->bindValue(':property_id', $data['property_id'], PDO::PARAM_INT);
        $stmt->bindValue(':start_date', $data['start_date']);
        $stmt->bindValue(':lease_term', $data['lease_term'], PDO::PARAM_INT);
        
        $stmt->execute();
        return $this->db->lastInsertId();
    }
    
    /**
     * Удаление арендатора
     * @param int $id Идентификатор арендатора
     * @return bool Результат операции
     */
    public function deleteTenant(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tenants WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
    
    /**
     * Удаление недвижимости
     * @param int $id Идентификатор недвижимости
     * @return bool Результат операции
     */
    public function deleteProperty(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM rental_properties WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
    
    /**
     * Удаление арендной информации
     * @param int $id Идентификатор арендной информации
     * @return bool Результат операции
     */
    public function deleteRentalInfo(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM rental_info WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
    
    /**
     * Обновление арендатора
     * @param int $id Идентификатор арендатора
     * @param array $data Данные для обновления
     * @return bool Результат операции
     */
    public function updateTenant(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tenants 
            SET code = :code, last_name = :last_name 
            WHERE id = :id
        ");
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':last_name', $data['last_name']);
        
        return $stmt->execute();
    }
    
    /**
     * Обновление недвижимости
     * @param int $id Идентификатор недвижимости
     * @param array $data Данные для обновления
     * @return bool Результат операции
     */
    public function updateProperty(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE rental_properties 
            SET code = :code, type = :type, monthly_rent = :monthly_rent 
            WHERE id = :id
        ");
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':code', $data['code']);
        $stmt->bindValue(':type', $data['type']);
        $stmt->bindValue(':monthly_rent', $data['monthly_rent'], PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    /**
     * Обновление арендной информации
     * @param int $id Идентификатор арендной информации
     * @param array $data Данные для обновления
     * @return bool Результат операции
     */
    public function updateRentalInfo(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE rental_info 
            SET tenant_id = :tenant_id, property_id = :property_id, start_date = :start_date, lease_term = :lease_term 
            WHERE id = :id
        ");
        
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id', $data['tenant_id'], PDO::PARAM_INT);
        $stmt->bindValue(':property_id', $data['property_id'], PDO::PARAM_INT);
        $stmt->bindValue(':start_date', $data['start_date']);
        $stmt->bindValue(':lease_term', $data['lease_term'], PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // Запросы для отчетов
    
    /**
     * Список недвижимости определенного типа, отсортированный по алфавиту в порядке убывания или по цене в порядке возрастания
     * @param string $type Тип недвижимости
     * @param string $sortBy Критерий сортировки (type или price)
     * @return array Массив недвижимости
     */
    public function getPropertiesByType(string $type, string $sortBy = 'type'): array
    {
        $orderBy = $sortBy === 'price' ? 'monthly_rent ASC' : 'type DESC';
        $stmt = $this->db->prepare("
            SELECT * FROM rental_properties 
            WHERE type LIKE :type
            ORDER BY {$orderBy}
        ");
        $stmt->bindValue(':type', "%{$type}%");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список арендаторов для каждой недвижимости с количеством аренд
     * @return array Массив арендаторов с информацией об аренде
     */
    public function getTenantsForProperties(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                rp.id as property_id,
                rp.code as property_code,
                rp.type as property_type,
                rp.monthly_rent,
                t.id as tenant_id,
                t.code as tenant_code,
                t.last_name as tenant_last_name,
                COUNT(ri.id) as rental_count
            FROM rental_properties rp
            LEFT JOIN rental_info ri ON rp.id = ri.property_id
            LEFT JOIN tenants t ON ri.tenant_id = t.id
            GROUP BY rp.id, t.id
            ORDER BY rp.type, t.last_name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список недвижимости, которая никогда не сдавалась
     * @return array Массив недвижимости
     */
    public function getPropertiesNeverRented(): array
    {
        $stmt = $this->db->prepare("
            SELECT rp.* 
            FROM rental_properties rp
            LEFT JOIN rental_info ri ON rp.id = ri.property_id
            WHERE ri.property_id IS NULL
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список недвижимости, которая сдавалась более 3 раз
     * @return array Массив недвижимости
     */
    public function getPropertiesRentedMoreThan3Times(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                rp.*,
                COUNT(ri.id) as rental_count
            FROM rental_properties rp
            JOIN rental_info ri ON rp.id = ri.property_id
            GROUP BY rp.id
            HAVING COUNT(ri.id) > 3
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список недвижимости, которая сдавалась более 2 раз и со сроком аренды более 1 года, с дополнительным столбцом
     * @return array Массив недвижимости
     */
    public function getPropertiesRentedMoreThan2TimesWithLongTerm(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                rp.*,
                COUNT(ri.id) as rental_count,
                COUNT(CASE WHEN ri.lease_term > 12 THEN 1 END) as long_term_rentals
            FROM rental_properties rp
            JOIN rental_info ri ON rp.id = ri.property_id
            WHERE ri.lease_term > 12
            GROUP BY rp.id
            HAVING COUNT(ri.id) > 2
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список недвижимости с количеством аренд и общей суммой аренды
     * @return array Массив недвижимости
     */
    public function getPropertiesWithRentalStats(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                rp.*,
                COUNT(ri.id) as rental_count,
                SUM(rp.monthly_rent * ri.lease_term) as total_rent
            FROM rental_properties rp
            LEFT JOIN rental_info ri ON rp.id = ri.property_id
            GROUP BY rp.id
            ORDER BY rp.type
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список арендаторов с количеством аренд и средним сроком аренды
     * @return array Массив арендаторов
     */
    public function getTenantsWithRentalStats(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                COUNT(ri.id) as rental_count,
                AVG(ri.lease_term) as avg_lease_term
            FROM tenants t
            LEFT JOIN rental_info ri ON t.id = ri.tenant_id
            GROUP BY t.id
            ORDER BY t.last_name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список всех арендованных недвижимостей указанного типа за указанный год и квартал
     * @param string $propertyType Тип недвижимости
     * @param int $year Год
     * @param int $quarter Квартал (1-4)
     * @return array Массив недвижимости
     */
    public function getRentedPropertiesByTypeAndQuarter(string $propertyType, int $year, int $quarter): array
    {
        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $quarter * 3;
        
        $stmt = $this->db->prepare("
            SELECT 
                rp.*,
                ri.start_date,
                t.last_name as tenant_last_name
            FROM rental_info ri
            JOIN rental_properties rp ON ri.property_id = rp.id
            JOIN tenants t ON ri.tenant_id = t.id
            WHERE rp.type LIKE :propertyType
                AND YEAR(ri.start_date) = :year
                AND MONTH(ri.start_date) BETWEEN :startMonth AND :endMonth
            ORDER BY ri.start_date
        ");
        $stmt->bindValue(':propertyType', "%{$propertyType}%");
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->bindValue(':startMonth', $startMonth, PDO::PARAM_INT);
        $stmt->bindValue(':endMonth', $endMonth, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Список арендаторов и количество различных недвижимостей, которые они арендуют
     * @return array Массив арендаторов
     */
    public function getTenantsWithDifferentProperties(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                COUNT(DISTINCT ri.property_id) as different_properties_count
            FROM tenants t
            LEFT JOIN rental_info ri ON t.id = ri.tenant_id
            GROUP BY t.id
            ORDER BY t.last_name
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Обновленная арендная плата для определенных типов недвижимости (увеличенная на 12%)
     * @param string $propertyType Тип недвижимости
     * @return array Массив недвижимости с новой арендной платой
     */
    public function getAdjustedRents(string $propertyType): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                *,
                monthly_rent * 1.12 as adjusted_rent,
                monthly_rent as original_rent
            FROM rental_properties
            WHERE type LIKE :propertyType
        ");
        $stmt->bindValue(':propertyType', "%{$propertyType}%");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Получение всех типов недвижимости
     * @return array Массив типов недвижимости
     */
    public function getAllPropertyTypes(): array
    {
        $stmt = $this->db->prepare("SELECT DISTINCT type FROM rental_properties ORDER BY type");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Получение всех лет, в которых были аренды
     * @return array Массив лет
     */
    public function getYearsWithRentals(): array
    {
        $stmt = $this->db->prepare("SELECT DISTINCT YEAR(start_date) as year FROM rental_info ORDER BY year DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>