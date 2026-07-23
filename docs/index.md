# Belongs To Many Field for Filament

## Installation

First, install this package via the Composer package manager:

```bash
composer require wotz/filament-belongs-to-many
```

In an effort to align with Filament's theming methodology you will need to use a custom theme to use this plugin.

> **Note**
> If you have not set up a custom theme and are using a Panel follow the instructions in the [Filament Docs](https://filamentphp.com/docs/3.x/panels/themes#creating-a-custom-theme) first. The following applies to both the Panels Package and the standalone Forms package.

Import the plugin's stylesheet (if not already included) into your theme's css file.

```css
@import '../../../../vendor/wotz/filament-belongs-to-many/resources/css/plugin.css';
```

## Usage

To use this field, you'll need to add it to your resource's Edit page, like so:

```php
use Wotz\BelongsToMany\Forms\Components\BelongsToManyInput;

public static function form(Form $form)
{
    return $form
        ->schema([
            BelongsToManyInput::make('tags'),
        ]);
}
```

The field will automatically detect the relationship between the current model and the model you're trying to relate to.

## Customizing the field

All the following methods are available on the field and work like any other field in Filament.
Meaning that you can add callbacks etc.

### displayLabelUsing()

You can customize the label of the field by using the `displayLabelUsing()` method:

```php
BelongsToMany::make('tags')
    ->displayLabelUsing('title'),
```

### displayViewUsing()

If you want more customization, you can use the `displayViewUsing()` method to change the blade view that is loaded:

```php
BelongsToMany::make('tags')
    ->displayViewUsing('my-custom-view'),
```

This allows you to customize the way your item is displayed in the list, for example with the following blade file:

```php
<div class="py-2 px-4">
    <p class="font-bold">
        {{ $label }}
    </p>

    <p class="opacity-50">
        {{ $item->slug }} ({{ $item->id }})
    </p>
</div>
```

The `$label` variable contains the label of the item, loaded using the `displayLabelUsing()` and the `$item` variable contains the model instance, meaning that you have full access to your model to load images or other relations, etc.

### sortable()

You can make the field sortable by using the `sortable()` method:

```php
BelongsToMany::make('tags')
    ->sortable('sort_field'),
```

This will allow you to drag and drop the items in the list to change their order. Make sure you add the following to your relationship:

```php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class)
        ->withPivot('sort_field')
        ->orderBy('sort_field');
}
```

### pagination()

You can change the amount of items that are shown per page by using the `perPage()` method:

```php
BelongsToMany::make('tags')
    ->pagination(10),
```

By default, this value will be 10, but you can change it to whatever you want.
If you do not wish to paginate the items, you can set the value to `false`, note that this will load all items at once, which may result in poor performance:

```php
BelongsToMany::make('tags')
    ->pagination(false),
```
