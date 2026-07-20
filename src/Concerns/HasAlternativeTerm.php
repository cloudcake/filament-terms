<?php

namespace Cloudcake\Terms\Concerns;

use Cloudcake\Terms\Terminology;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Stringable;

/**
 * @mixin Resource|Page
 */
trait HasAlternativeTerm
{
    public function getTitle(): string|Htmlable
    {
        return static::getNavigationLabel();
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getTitle();
    }

    public static function getModelLabel(): string
    {
        return static::getSingular();
    }

    public static function getOriginalModelLabel(): string
    {
        return parent::getModelLabel();
    }

    public static function getSingular(): Stringable
    {
        return str(static::getNavigationLabel())->singular();
    }

    public static function getPlural(): Stringable
    {
        return str(static::getNavigationLabel())->plural();
    }

    public static function getNavigationLabel(): string
    {
        $fallback = str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->remove(' resource')
            ->ucwords();

        return str(Terminology::getTerm(static::class . '.name', $fallback))
            ->when(fn() => is_a(static::class, Resource::class, true), fn($str) => $str->plural())
            ->toString();
    }

    public static function getNavigationIcon(): string|null|Htmlable|BackedEnum
    {
        return Terminology::getTerm(static::class . '.icon', parent::getNavigationIcon());
    }

    public static function getNavigationBadge(): ?string
    {
        return Terminology::getTerm(static::class . '.badge', parent::getNavigationBadge());
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return Terminology::getTerm(static::class . '.color', parent::getNavigationBadgeColor());
    }
}