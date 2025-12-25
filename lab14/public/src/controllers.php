<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/models.php';
require_once __DIR__ . '/views.php';

/**
 * Контроллер для работы с базой данных аренды
 */
class RentalController
{
    /**
     * Репозиторий для работы с данными
     */
    private RentalRepository $repository;
    
    /**
     * Конструктор контроллера
     * @param PDO $db Объект соединения с базой данных
     */
    public function __construct(PDO $db)
    {
        $this->repository = new RentalRepository($db);
    }
    
    /**
     * Отображение главной страницы
     */
    public function index(): void
    {
        $tenants = $this->repository->getAllTenants();
        $properties = $this->repository->getAllProperties();
        $rentalInfos = $this->repository->getAllRentalInfos();
        
        $html = renderLayout('index', [
            'tenants' => $tenants,
            'properties' => $properties,
            'rentalInfos' => $rentalInfos
        ]);
        echo $html;
    }
    
    /**
     * Отображение формы добавления арендатора
     */
    public function showAddTenantForm(): void
    {
        $html = renderLayout('add_tenant', [
            'errors' => [],
            'formData' => []
        ]);
        echo $html;
    }
    
    /**
     * Отображение формы добавления недвижимости
     */
    public function showAddPropertyForm(): void
    {
        $html = renderLayout('add_property', [
            'errors' => [],
            'formData' => []
        ]);
        echo $html;
    }
    
    /**
     * Отображение формы добавления арендной информации
     */
    public function showAddRentalInfoForm(): void
    {
        $tenants = $this->repository->getAllTenants();
        $properties = $this->repository->getAllProperties();
        
        $html = renderLayout('add_rental_info', [
            'tenants' => $tenants,
            'properties' => $properties,
            'errors' => [],
            'formData' => []
        ]);
        echo $html;
    }
    
    /**
     * Добавление нового арендатора
     */
    public function addTenant(): void
    {
        $errors = [];
        $formData = $_POST ?? [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            
            // Валидация данных
            if (empty($code)) $errors[] = 'Код арендатора обязателен для заполнения';
            if (empty($lastName)) $errors[] = 'Фамилия арендатора обязательна для заполнения';
            
            if (empty($errors)) {
                $data = [
                    'code' => $code,
                    'last_name' => $lastName
                ];
                
                $id = $this->repository->createTenant($data);
                header('Location: /?success=tenant_added');
                exit;
            }
        }
        
        $html = renderLayout('add_tenant', [
            'errors' => $errors,
            'formData' => $formData
        ]);
        echo $html;
    }
    
    /**
     * Добавление новой недвижимости
     */
    public function addProperty(): void
    {
        $errors = [];
        $formData = $_POST ?? [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['code'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $monthlyRent = trim($_POST['monthly_rent'] ?? '');
            
            // Валидация данных
            if (empty($code)) $errors[] = 'Код недвижимости обязателен для заполнения';
            if (empty($type)) $errors[] = 'Тип недвижимости обязателен для заполнения';
            if (empty($monthlyRent) || !is_numeric($monthlyRent) || (float)$monthlyRent <= 0) $errors[] = 'Ежемесячная арендная плата должна быть положительным числом';
            
            if (empty($errors)) {
                $data = [
                    'code' => $code,
                    'type' => $type,
                    'monthly_rent' => (float)$monthlyRent
                ];
                
                $id = $this->repository->createProperty($data);
                header('Location: /?success=property_added');
                exit;
            }
        }
        
        $html = renderLayout('add_property', [
            'errors' => $errors,
            'formData' => $formData
        ]);
        echo $html;
    }
    
    /**
     * Добавление новой арендной информации
     */
    public function addRentalInfo(): void
    {
        $tenants = $this->repository->getAllTenants();
        $properties = $this->repository->getAllProperties();
        $errors = [];
        $formData = $_POST ?? [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tenantId = $_POST['tenant_id'] ?? '';
            $propertyId = $_POST['property_id'] ?? '';
            $startDate = trim($_POST['start_date'] ?? '');
            $leaseTerm = trim($_POST['lease_term'] ?? '');
            
            // Валидация данных
            if (empty($tenantId) || !is_numeric($tenantId) || (int)$tenantId <= 0) $errors[] = 'Выберите арендатора';
            if (empty($propertyId) || !is_numeric($propertyId) || (int)$propertyId <= 0) $errors[] = 'Выберите недвижимость';
            if (empty($startDate)) $errors[] = 'Дата начала аренды обязательна';
            if (empty($leaseTerm) || !is_numeric($leaseTerm) || (int)$leaseTerm <= 0) $errors[] = 'Срок аренды должен быть положительным числом';
            
            if (empty($errors)) {
                $data = [
                    'tenant_id' => (int)$tenantId,
                    'property_id' => (int)$propertyId,
                    'start_date' => $startDate,
                    'lease_term' => (int)$leaseTerm
                ];
                
                $id = $this->repository->createRentalInfo($data);
                header('Location: /?success=rental_info_added');
                exit;
            }
        }
        
        $html = renderLayout('add_rental_info', [
            'tenants' => $tenants,
            'properties' => $properties,
            'errors' => $errors,
            'formData' => $formData
        ]);
        echo $html;
    }
    
    /**
     * Удаление арендатора по идентификатору
     * @param string $id Идентификатор арендатора
     */
    public function deleteTenant(string $id): void
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            header('Location: /?error=invalid_id');
            exit;
        }
        
        $tenantId = (int)$id;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->repository->deleteTenant($tenantId)) {
                header('Location: /?deleted=tenant');
            } else {
                header('Location: /?error=not_found');
            }
            exit;
        }
        
        $tenant = $this->repository->getTenantById($tenantId);
        
        if (!$tenant) {
            header('Location: /?error=not_found');
            exit;
        }
        
        $html = renderLayout('delete_tenant', ['tenant' => $tenant]);
        echo $html;
    }
    
    /**
     * Удаление недвижимости по идентификатору
     * @param string $id Идентификатор недвижимости
     */
    public function deleteProperty(string $id): void
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            header('Location: /?error=invalid_id');
            exit;
        }
        
        $propertyId = (int)$id;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->repository->deleteProperty($propertyId)) {
                header('Location: /?deleted=property');
            } else {
                header('Location: /?error=not_found');
            }
            exit;
        }
        
        $property = $this->repository->getPropertyById($propertyId);
        
        if (!$property) {
            header('Location: /?error=not_found');
            exit;
        }
        
        $html = renderLayout('delete_property', ['property' => $property]);
        echo $html;
    }
    
    /**
     * Удаление арендной информации по идентификатору
     * @param string $id Идентификатор арендной информации
     */
    public function deleteRentalInfo(string $id): void
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            header('Location: /?error=invalid_id');
            exit;
        }
        
        $rentalInfoId = (int)$id;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->repository->deleteRentalInfo($rentalInfoId)) {
                header('Location: /?deleted=rental_info');
            } else {
                header('Location: /?error=not_found');
            }
            exit;
        }
        
        $rentalInfo = $this->repository->getRentalInfoById($rentalInfoId);
        
        if (!$rentalInfo) {
            header('Location: /?error=not_found');
            exit;
        }
        
        $html = renderLayout('delete_rental_info', ['rental_info' => $rentalInfo]);
        echo $html;
    }
    
    // Отчеты
    
    /**
     * Отображение отчета: список недвижимости определенного типа
     */
    public function showPropertyTypeReport(): void
    {
        $type = $_GET['type'] ?? '';
        $sortBy = $_GET['sort_by'] ?? 'type';
        
        $properties = $this->repository->getPropertiesByType($type, $sortBy);
        $html = renderLayout('report_property_type', [
            'properties' => $properties,
            'type' => $type,
            'sortBy' => $sortBy
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список арендаторов для каждой недвижимости
     */
    public function showTenantsForPropertiesReport(): void
    {
        $tenantsForProperties = $this->repository->getTenantsForProperties();
        $html = renderLayout('report_tenants_for_properties', [
            'tenantsForProperties' => $tenantsForProperties
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список недвижимости, которая никогда не сдавалась
     */
    public function showPropertiesNeverRentedReport(): void
    {
        $properties = $this->repository->getPropertiesNeverRented();
        $html = renderLayout('report_properties_never_rented', [
            'properties' => $properties
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список недвижимости, которая сдавалась более 3 раз
     */
    public function showPropertiesRentedMoreThan3TimesReport(): void
    {
        $properties = $this->repository->getPropertiesRentedMoreThan3Times();
        $html = renderLayout('report_properties_rented_more_than_3_times', [
            'properties' => $properties
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список недвижимости, которая сдавалась более 2 раз и со сроком аренды более 1 года
     */
    public function showPropertiesRentedMoreThan2TimesWithLongTermReport(): void
    {
        $properties = $this->repository->getPropertiesRentedMoreThan2TimesWithLongTerm();
        $html = renderLayout('report_properties_rented_more_than_2_times_with_long_term', [
            'properties' => $properties
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список недвижимости с количеством аренд и общей суммой аренды
     */
    public function showPropertiesWithRentalStatsReport(): void
    {
        $properties = $this->repository->getPropertiesWithRentalStats();
        $html = renderLayout('report_properties_with_rental_stats', [
            'properties' => $properties
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список арендаторов с количеством аренд и средним сроком аренды
     */
    public function showTenantsWithRentalStatsReport(): void
    {
        $tenants = $this->repository->getTenantsWithRentalStats();
        $html = renderLayout('report_tenants_with_rental_stats', [
            'tenants' => $tenants
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список всех арендованных недвижимостей указанного типа за указанный год и квартал
     */
    public function showRentedPropertiesByTypeAndQuarterReport(): void
    {
        $propertyType = $_GET['property_type'] ?? '';
        $year = isset($_GET['year']) && is_numeric($_GET['year']) ? (int)$_GET['year'] : date('Y');
        $quarter = isset($_GET['quarter']) && is_numeric($_GET['quarter']) && $_GET['quarter'] >= 1 && $_GET['quarter'] <= 4 
                   ? (int)$_GET['quarter'] : 1;
        
        $properties = $this->repository->getRentedPropertiesByTypeAndQuarter($propertyType, $year, $quarter);
        $years = $this->repository->getYearsWithRentals();
        $html = renderLayout('report_rented_properties_by_type_and_quarter', [
            'properties' => $properties,
            'propertyType' => $propertyType,
            'year' => $year,
            'quarter' => $quarter,
            'years' => $years
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: список арендаторов и количество различных недвижимостей, которые они арендуют
     */
    public function showTenantsWithDifferentPropertiesReport(): void
    {
        $tenants = $this->repository->getTenantsWithDifferentProperties();
        $html = renderLayout('report_tenants_with_different_properties', [
            'tenants' => $tenants
        ]);
        echo $html;
    }
    
    /**
     * Отображение отчета: обновленная арендная плата для определенных типов недвижимости (увеличенная на 12%)
     */
    public function showAdjustedRentsReport(): void
    {
        $propertyType = $_GET['property_type'] ?? '';
        $properties = $this->repository->getAdjustedRents($propertyType);
        $html = renderLayout('report_adjusted_rents', [
            'properties' => $properties,
            'propertyType' => $propertyType
        ]);
        echo $html;
    }
}
?>