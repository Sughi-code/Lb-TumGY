from django.contrib import admin
from .models import Category

admin.site.empty_value_display = 'Не задано'

class CategoryAdmin(admin.ModelAdmin): 
    #filter_horizontal = ('toppings',)
    list_display = (
        'title',
        'description',
        'slug',
        'is_published',
        'created_at'
    )
    list_editable = (
        #'title',
        'description',
        'slug',
        'is_published'
    )    
    search_fields = ('title',) 
    list_filter = ('is_published',)
    list_display_links = ('title',)
admin.site.register(Category, CategoryAdmin)


from .models import Location

class LocationAdmin(admin.ModelAdmin): 
    #filter_horizontal = ('toppings',)
    list_display = (
        'name',
        'is_published',
        'created_at',
    )
    list_editable = (
        #'name',
        'is_published',
    )    
    search_fields = ('name',) 
    list_filter = ('is_published',)
    list_display_links = ('name',)


admin.site.register(Location, LocationAdmin)


from .models import Post

class PostAdmin(admin.ModelAdmin): 
    #filter_horizontal = ('toppings',)
    list_display = (
        'title',
        'text',
        'pub_date',
        'author',
        'location',
        'category',
        'is_published',
        'created_at',
    )
    list_editable = (
        #'title',
        'text',
        'pub_date',
        'author',
        'location',
        'category',
        'is_published',
    )    
    search_fields = ('title',) 
    list_filter = ('category',)
    list_display_links = ('title',)

admin.site.register(Post, PostAdmin)