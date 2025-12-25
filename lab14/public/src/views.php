<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

/**
 * Генерация HTML для главной страницы
 * @param array $tenants Массив арендаторов
 * @param array $properties Массив недвижимости
 * @param array $rentalInfos Массив информации об аренде
 * @return string HTML содержимое
 */
function renderIndexView(array $tenants, array $properties, array $rentalInfos): string
{
    ob_start();
    ?>
    <h2>База данных аренды</h2>
    
    <div class="section">
        <h3>Арендаторы</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Код арендатора</th>
                        <th>Фамилия</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td><?= htmlspecialchars($tenant['id']) ?></td>
                        <td><?= htmlspecialchars($tenant['code']) ?></td>
                        <td><?= htmlspecialchars($tenant['last_name']) ?></td>
                        <td><?= htmlspecialchars($tenant['created_at']) ?></td>
                        <td>
                            <form method="POST" action="/delete-tenant/<?= $tenant['id'] ?>" style="display: inline;">
                                <button type="submit" class="btn-delete" onclick="return confirm('Вы уверены, что хотите удалить этого арендатора?')">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="section">
        <h3>Недвижимость</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Код недвижимости</th>
                        <th>Тип</th>
                        <th>Ежемесячная арендная плата</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($properties as $property): ?>
                    <tr>
                        <td><?= htmlspecialchars($property['id']) ?></td>
                        <td><?= htmlspecialchars($property['code']) ?></td>
                        <td><?= htmlspecialchars($property['type']) ?></td>
                        <td><?= number_format($property['monthly_rent'], 2, '.', ' ') ?></td>
                        <td><?= htmlspecialchars($property['created_at']) ?></td>
                        <td>
                            <form method="POST" action="/delete-property/<?= $property['id'] ?>" style="display: inline;">
                                <button type="submit" class="btn-delete" onclick="return confirm('Вы уверены, что хотите удалить эту недвижимость?')">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="section">
        <h3>Информация об аренде</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ID Арендатора</th>
                        <th>ID Недвижимости</th>
                        <th>Дата начала аренды</th>
                        <th>Срок аренды (мес)</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentalInfos as $rentalInfo): ?>
                    <tr>
                        <td><?= htmlspecialchars($rentalInfo['id']) ?></td>
                        <td><?= htmlspecialchars($rentalInfo['tenant_id']) ?></td>
                        <td><?= htmlspecialchars($rentalInfo['property_id']) ?></td>
                        <td><?= htmlspecialchars($rentalInfo['start_date']) ?></td>
                        <td><?= htmlspecialchars($rentalInfo['lease_term']) ?></td>
                        <td><?= htmlspecialchars($rentalInfo['created_at']) ?></td>
                        <td>
                            <form method="POST" action="/delete-rental-info/<?= $rentalInfo['id'] ?>" style="display: inline;">
                                <button type="submit" class="btn-delete" onclick="return confirm('Вы уверены, что хотите удалить эту информацию об аренде?')">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="actions">
        <a href="/add-tenant" class="btn-primary">Добавить арендатора</a>
        <a href="/add-property" class="btn-primary">Добавить недвижимость</a>
        <a href="/add-rental-info" class="btn-primary">Добавить информацию об аренде</a>
    </div>
    
    <div class="reports-section">
        <h3>Отчеты</h3>
        <div class="reports-grid">
            <a href="/report/property-type" class="report-link">Список недвижимости определенного типа</a>
            <a href="/report/tenants-for-properties" class="report-link">Арендаторы для каждой недвижимости</a>
            <a href="/report/properties-never-rented" class="report-link">Недвижимость, которая никогда не сдавалась</a>
            <a href="/report/properties-rented-more-than-3-times" class="report-link">Недвижимость, сдававшаяся более 3 раз</a>
            <a href="/report/properties-rented-more-than-2-times-with-long-term" class="report-link">Недвижимость с долгосрочной арендой</a>
            <a href="/report/properties-with-rental-stats" class="report-link">Статистика по недвижимости</a>
            <a href="/report/tenants-with-rental-stats" class="report-link">Статистика по арендаторам</a>
            <a href="/report/rented-properties-by-type-and-quarter" class="report-link">Арендованные недвижимости за квартал</a>
            <a href="/report/tenants-with-different-properties" class="report-link">Арендаторы и разные недвижимости</a>
            <a href="/report/adjusted-rents" class="report-link">Обновленная арендная плата</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Генерация HTML для формы добавления
 * @param array $ageGroups Массив возрастных групп
 * @param array $errors Массив ошибок валидации
 * @param array $formData Введенные данные формы
 * @return string HTML содержимое
 */
function renderAddView(array $ageGroups, array $errors, array $formData): string
{
    ob_start();
    ?>
    <h2>Добавить человека</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/add">
        <div class="form-group">
            <label for="full_name">ФИО:</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($formData['full_name'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="weight">Вес (кг):</label>
            <input type="number" id="weight" name="weight" step="0.01" value="<?= htmlspecialchars($formData['weight'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="height">Рост (см):</label>
            <input type="number" id="height" name="height" step="0.01" value="<?= htmlspecialchars($formData['height'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="birth_date">Дата рождения:</label>
            <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($formData['birth_date'] ?? date('Y-m-d')) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Пол:</label>
            <div class="radio-group">
                <input type="radio" id="gender_male" name="gender" value="male" <?= ($formData['gender'] ?? 'male') === 'male' ? 'checked' : '' ?> required>
                <label for="gender_male">Мужской</label>
                
                <input type="radio" id="gender_female" name="gender" value="female" <?= ($formData['gender'] ?? '') === 'female' ? 'checked' : '' ?>>
                <label for="gender_female">Женский</label>
            </div>
        </div>
        
        <div class="form-group">
            <label for="birth_place">Место рождения:</label>
            <input type="text" id="birth_place" name="birth_place" value="<?= htmlspecialchars($formData['birth_place'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="age_group_id">Возрастная группа:</label>
            <select id="age_group_id" name="age_group_id" required>
                <option value="">Выберите группу</option>
                <?php foreach ($ageGroups as $group): ?>
                    <option value="<?= $group['id'] ?>" <?= ($formData['age_group_id'] ?? '') == $group['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($group['name']) ?> (<?= $group['min_age'] ?>-<?= $group['max_age'] ?> лет)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">Добавить</button>
            <a href="/" class="btn-secondary">Отмена</a>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Генерация HTML для подтверждения удаления
 * @param array $person Данные о человеке
 * @return string HTML содержимое
 */
function renderDeleteView(array $person): string
{
    ob_start();
    $birthDate = new DateTime($person['birth_date']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    ?>
    <h2>Удаление записи</h2>
    <div class="confirmation-box">
        <p>Вы уверены, что хотите удалить следующую запись?</p>
        <div class="person-details">
            <p><strong>ФИО:</strong> <?= htmlspecialchars($person['full_name']) ?></p>
            <p><strong>Возраст:</strong> <?= $age ?> лет</p>
            <p><strong>Место рождения:</strong> <?= htmlspecialchars($person['birth_place']) ?></p>
        </div>
        
        <form method="POST" action="/delete/<?= $person['id'] ?>">
            <div class="form-actions">
                <button type="submit" class="btn-danger">Да, удалить</button>
                <a href="/" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Генерация HTML для отчета по возрасту
 * @param int $minAge Минимальный возраст
 * @param int $count Количество людей
 * @return string HTML содержимое
 */
function renderAgeReportView(int $minAge, int $count): string
{
    ob_start();
    ?>
    <h2>Отчет: Количество лиц, возраст которых больше указанного</h2>
    <div class="report-container">
        <form method="GET" action="/report/age" class="filter-form">
            <div class="form-group">
                <label for="min_age">Минимальный возраст:</label>
                <input type="number" id="min_age" name="min_age" value="<?= htmlspecialchars($minAge) ?>" min="0" max="120" required>
                <button type="submit" class="btn-primary">Показать</button>
            </div>
        </form>
        
        <div class="report-result">
            <h3>Результат:</h3>
            <p>Количество людей старше <?= htmlspecialchars($minAge) ?> лет: <strong><?= $count ?></strong></p>
        </div>
        
        <div class="chart-container">
            <canvas id="ageChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('ageChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                 {
                    labels: ['Люди старше <?= $minAge ?> лет'],
                    datasets: [{
                        label: 'Количество человек',
                         [<?= $count ?>],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Генерация HTML для отчета по полу
 * @param array $stats Статистика по полу
 * @return string HTML содержимое
 */
function renderGenderStatsReportView(array $stats): string
{
    ob_start();
    ?>
    <h2>Отчет: Средний рост и средний вес отдельно для мужчин и женщин</h2>
    <div class="report-container">
        <div class="report-result">
            <h3>Статистика по полу:</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Пол</th>
                        <th>Средний рост (см)</th>
                        <th>Средний вес (кг)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                        <tr>
                            <td><?= $stat['gender'] === 'male' ? 'Мужской' : 'Женский' ?></td>
                            <td><?= number_format($stat['avg_height'], 2) ?></td>
                            <td><?= number_format($stat['avg_weight'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="chart-container">
            <canvas id="genderChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('genderChart').getContext('2d');
            
            const genders = <?= json_encode(array_map(fn($s) => $s['gender'] === 'male' ? 'Мужской' : 'Женский', $stats)) ?>;
            const avgHeights = <?= json_encode(array_map(fn($s) => $s['avg_height'], $stats)) ?>;
            const avgWeights = <?= json_encode(array_map(fn($s) => $s['avg_weight'], $stats)) ?>;
            
            new Chart(ctx, {
                type: 'bar',
                 {
                    labels: genders,
                    datasets: [
                        {
                            label: 'Средний рост (см)',
                             avgHeights,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Средний вес (кг)',
                             avgWeights,
                            backgroundColor: 'rgba(255, 99, 132, 0.6)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Средние показатели по полу'
                        }
                    }
                }
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Генерация HTML для отчета по весу выше среднего
 * @param array $stats Статистика по возрастным группам
 * @return string HTML содержимое
 */
function renderWeightAboveAvgReportView(array $stats): string
{
    ob_start();
    ?>
    <h2>Отчет: Количество лиц, имеющих вес выше среднего по каждой возрастной группе</h2>
    <div class="report-container">
        <div class="report-result">
            <h3>Статистика по возрастным группам:</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Возрастная группа</th>
                        <th>Средний вес (кг)</th>
                        <th>Количество людей с весом выше среднего</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['age_group']) ?></td>
                            <td><?= number_format($stat['avg_weight'], 2) ?></td>
                            <td><?= $stat['count_above_avg'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="chart-container">
            <canvas id="weightChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('weightChart').getContext('2d');
            
            const ageGroups = <?= json_encode(array_map(fn($s) => $s['age_group'], $stats)) ?>;
            const countsAboveAvg = <?= json_encode(array_map(fn($s) => $s['count_above_avg'], $stats)) ?>;
            const avgWeights = <?= json_encode(array_map(fn($s) => $s['avg_weight'], $stats)) ?>;
            
            new Chart(ctx, {
                type: 'bar',
                 {
                    labels: ageGroups,
                    datasets: [
                        {
                            label: 'Количество людей с весом выше среднего',
                             countsAboveAvg,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Средний вес (кг)',
                             avgWeights,
                            backgroundColor: 'rgba(153, 102, 255, 0.6)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1,
                            type: 'line',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Количество людей'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Средний вес (кг)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Вес выше среднего по возрастным группам'
                        }
                    }
                }
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Генерация HTML для страницы 404
 * @param array $data Данные для страницы
 * @return string HTML содержимое
 */
function render404View(array $data): string
{
    ob_start();
    ?>
    <h1>404 - Страница не найдена</h1>
    <p><?= htmlspecialchars($data['message'] ?? 'Запрашиваемая страница не существует') ?></p>
    <p><a href="/" class="btn-primary">Вернуться на главную</a></p>
    <?php
    return ob_get_clean();
}

/**
 * Генерация полного HTML макета
 * @param string $view Название представления
 * @param array $data Данные для представления
 * @return string Полный HTML документ
 */
function renderLayout(string $view, array $data = []): string
{
    extract($data);
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>База данных аренды</title>
        <link rel="stylesheet" href="/style.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>
    <body>
        <div class="container">
            <header>
                <h1>База данных аренды</h1>
                <nav>
                    <ul>
                        <li><a href="/">Главная</a></li>
                        <li><a href="/add-tenant">Добавить арендатора</a></li>
                        <li><a href="/add-property">Добавить недвижимость</a></li>
                        <li><a href="/add-rental-info">Добавить аренду</a></li>
                        <li class="dropdown">
                            <a href="javascript:void(0)">Отчеты</a>
                            <div class="dropdown-content">
                                <a href="/report/property-type">Недвижимость определенного типа</a>
                                <a href="/report/tenants-for-properties">Арендаторы для каждой недвижимости</a>
                                <a href="/report/properties-never-rented">Недвижимость, которая никогда не сдавалась</a>
                                <a href="/report/properties-rented-more-than-3-times">Недвижимость, сдававшаяся более 3 раз</a>
                                <a href="/report/properties-rented-more-than-2-times-with-long-term">Недвижимость с долгосрочной арендой</a>
                                <a href="/report/properties-with-rental-stats">Статистика по недвижимости</a>
                                <a href="/report/tenants-with-rental-stats">Статистика по арендаторам</a>
                                <a href="/report/rented-properties-by-type-and-quarter">Арендованные недвижимости за квартал</a>
                                <a href="/report/tenants-with-different-properties">Арендаторы и разные недвижимости</a>
                                <a href="/report/adjusted-rents">Обновленная арендная плата</a>
                            </div>
                        </li>
                    </ul>
                </nav>
            </header>
            
            <main>
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert success">Запись успешно добавлена!</div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert success">Запись успешно удалена!</div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert error">
                        <?php 
                        $errorMessage = match($_GET['error']) {
                            'not_found' => 'Запись не найдена!',
                            'invalid_id' => 'Некорректный ID записи!',
                            default => 'Произошла ошибка при обработке запроса.',
                        };
                        echo htmlspecialchars($errorMessage);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php
                switch ($view) {
                    case 'index':
                        echo renderIndexView(
                            $tenants ?? [],
                            $properties ?? [],
                            $rentalInfos ?? []
                        );
                        break;
                    case 'add_tenant':
                        echo renderAddTenantView(
                            $errors ?? [],
                            $formData ?? []
                        );
                        break;
                    case 'add_property':
                        echo renderAddPropertyView(
                            $errors ?? [],
                            $formData ?? []
                        );
                        break;
                    case 'add_rental_info':
                        echo renderAddRentalInfoView(
                            $tenants ?? [],
                            $properties ?? [],
                            $errors ?? [],
                            $formData ?? []
                        );
                        break;
                    case 'delete_tenant':
                        echo renderDeleteTenantView($tenant ?? []);
                        break;
                    case 'delete_property':
                        echo renderDeletePropertyView($property ?? []);
                        break;
                    case 'delete_rental_info':
                        echo renderDeleteRentalInfoView($rental_info ?? []);
                        break;
                    case 'report_property_type':
                        echo renderPropertyTypeReportView(
                            $properties ?? [],
                            $type ?? '',
                            $sortBy ?? 'type'
                        );
                        break;
                    case 'report_tenants_for_properties':
                        echo renderTenantsForPropertiesReportView($tenantsForProperties ?? []);
                        break;
                    case 'report_properties_never_rented':
                        echo renderPropertiesNeverRentedReportView($properties ?? []);
                        break;
                    case 'report_properties_rented_more_than_3_times':
                        echo renderPropertiesRentedMoreThan3TimesReportView($properties ?? []);
                        break;
                    case 'report_properties_rented_more_than_2_times_with_long_term':
                        echo renderPropertiesRentedMoreThan2TimesWithLongTermReportView($properties ?? []);
                        break;
                    case 'report_properties_with_rental_stats':
                        echo renderPropertiesWithRentalStatsReportView($properties ?? []);
                        break;
                    case 'report_tenants_with_rental_stats':
                        echo renderTenantsWithRentalStatsReportView($tenants ?? []);
                        break;
                    case 'report_rented_properties_by_type_and_quarter':
                        echo renderRentedPropertiesByTypeAndQuarterReportView(
                            $properties ?? [],
                            $propertyType ?? '',
                            $year ?? date('Y'),
                            $quarter ?? 1,
                            $years ?? []
                        );
                        break;
                    case 'report_tenants_with_different_properties':
                        echo renderTenantsWithDifferentPropertiesReportView($tenants ?? []);
                        break;
                    case 'report_adjusted_rents':
                        echo renderAdjustedRentsReportView(
                            $properties ?? [],
                            $propertyType ?? ''
                        );
                        break;
                    case '404':
                        echo render404View($data ?? []);
                        break;
                    default:
                        echo '<p>Страница не найдена</p>';
                }
                ?>
            </main>
            
            <footer>
                <p>&copy; <?= date('Y') ?> База данных аренды. Все права защищены.</p>
            </footer>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>