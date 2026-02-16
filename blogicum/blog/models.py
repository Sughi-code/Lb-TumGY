from django.db import models
from django.contrib.auth import get_user_model


User = get_user_model()

class PublishedModel(models.Model):
    """Абстрактная модель для публикаций."""
    is_published = models.BooleanField(
        default=True, 
        verbose_name="Опубликовано",
        help_text = 'Снимите галочку, чтобы скрыть публикацию.'
    )    
    created_at = models.DateTimeField(
        auto_now_add=True,
        verbose_name="Дата создания"
    )
    
    class Meta:
        abstract = True


class Category(PublishedModel):
    """Модель для категорий."""
    title = models.CharField(
        max_length=256, 
        verbose_name="Название"
    )
    description = models.CharField(
        max_length=1024,
        verbose_name="Описание"
    )
    slug = models.SlugField(
        unique=True, 
        verbose_name="Слаг",
        help_text='Идентификатор страницы для URL; разрешены символы латиницы, цифры, дефис и подчёркивание.'
    )
    
    class Meta:
        verbose_name = 'Категория'
        verbose_name_plural = 'Категории'
    
    def __str__(self):
        return self.title


class Location(PublishedModel):
    """Модель для локаций."""
    name = models.CharField(
        max_length=256, 
        verbose_name="Название"
    )
    
    class Meta:
        verbose_name = 'Локация'
        verbose_name_plural = 'Локации'
    
    def __str__(self):
        return self.name  


class Post(PublishedModel):
    """Модель для постов."""
    title = models.CharField(
        max_length=256,
        verbose_name="Название"
    )
    text = models.TextField(  
        verbose_name="Текст"
    )
    pub_date = models.DateTimeField(
        verbose_name="Дата публикации",
        help_text='Если установить дату и время в будущем — можно делать отложенные публикации.'
    )
    author = models.ForeignKey(
        User,
        on_delete=models.CASCADE,
        verbose_name="Автор"
    )
    location = models.ForeignKey(
        Location,
        on_delete=models.SET_NULL, 
        related_name='Location',  
        null=True,
        blank=True, 
        verbose_name="Локация", 
    )
    category = models.ForeignKey( 
        Category,
        on_delete=models.SET_NULL,
        null=True,
        blank=True, 
        related_name='Category',
        verbose_name="Категория",  
    )
    
    class Meta:
        verbose_name = 'Пост'
        verbose_name_plural = 'Посты'
        ordering = ['-pub_date']  
    
    def __str__(self):
        return self.title