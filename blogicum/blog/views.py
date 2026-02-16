from django.shortcuts import render
from django.shortcuts import get_object_or_404
from django.db.models import Q
from .models import Post
from .models import Category
from datetime import datetime

def index(request):
    template = 'blog/index.html'
    posts = Post.objects.select_related('category', 'location', 'author').filter(
        Q(is_published=True) & 
        Q(pub_date__lte=datetime.now()) & 
        Q(category__is_published=True)
    )
    context = {'post_list': posts}
    return render(request, template, context)


def post_detail(request, id):
    template = 'blog/detail.html'
    post = get_object_or_404(
        Post.objects.select_related('category', 'location', 'author'),
        Q(id=id) & 
        Q(is_published=True) & 
        Q(category__is_published=True) & 
        Q(pub_date__lte=datetime.now())
    )
    context = {'post': post}
    return render(request, template, context)


def category_posts(request, category_slug):
    template = 'blog/category.html'
    category = get_object_or_404(Category, slug=category_slug, is_published=True)
    posts = Post.objects.select_related('category', 'location', 'author').filter(
        Q(category=category) &
        Q(is_published=True) &
        Q(pub_date__lte=datetime.now())
    )
    context = {'post_list': posts, 'category': category}
    return render(request, template, context)