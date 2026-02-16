from django.shortcuts import render
from django.shortcuts import get_object_or_404
from django.db.models import Q
from .models import Post
from .models import Category
from datetime import datetime

def index(request):
    template = 'blog/index.html'
    context = Post.objects.select_related('Category').filter(Q(is_published=True) & Q(pub_date__lte=str(datetime.now())) & Q(category__is_published=True) ) # как проверить время # post_card: pub_date, location, author, text
    return render(request, template, context)


def post_detail(request, pk):
    template = 'blog/detail.html'
    context = get_object_or_404(Post.select_related('Category').value( 'pub_date', 'location', 'author', 'text').filter( Q(pk=pk) & Q(is_published=True) & Q(Category__is_published=True) & Q(pub_date__lte=str(datetime.now()))))
    return render(request, template, context)


def category_posts(request, category_slug):
    template = 'blog/category.html'
    ctg = get_object_or_404( Category.value('slug').filter(is_published=True, slug=category_slug)) 
    context = Category.objects.select_related('Post').filter(Q(slug=ctg.slug) & Q(is_published=True) & Q(pub_date__lte=str(datetime.now())) )
    return render(request, template, context)