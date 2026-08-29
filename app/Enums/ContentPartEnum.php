<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

enum ContentPartEnum: string
{
    use HasEnumHelpers;

    case IMAGE_GRID = 'IMAGE_GRID';
    case SHADCN_ACCORDION = 'SHADCN_ACCORDION';
    case SHADCN_CARD = 'SHADCN_CARD';
    case TIPTAP = 'TIPTAP';
    case HERO = 'HERO';
    case SPOTIFY_EMBED = 'SPOTIFY_EMBED';
    case SOCIAL_EMBED = 'SOCIAL_EMBED';
    case FLOW_GRAPH = 'FLOW_GRAPH';
    case NUMBER_STAT_SECTION = 'NUMBER_STAT_SECTION';
    case NEWS = 'NEWS';
    case CALENDAR = 'CALENDAR';
    case CONTENT_GRID = 'CONTENT_GRID';
    case TEXT_BOX = 'TEXT_BOX';
    case CAROUSEL_SLIDE_DECK = 'CAROUSEL_SLIDE_DECK';
    case HERO_CAROUSEL = 'HERO_CAROUSEL';
    case CARD_STACK = 'CARD_STACK';
    case PHOTO_GALLERY = 'PHOTO_GALLERY';
    case LINK_LIST = 'LINK_LIST';
    case EVENT_LIST = 'EVENT_LIST';
    case PERSON_QUOTE = 'PERSON_QUOTE';
    case SECTION = 'SECTION';
    case SPACER = 'SPACER';
    case TIMETABLE = 'TIMETABLE';

    public function label(): string
    {
        return match ($this) {
            self::IMAGE_GRID => 'image-grid',
            self::SHADCN_ACCORDION => 'shadcn-accordion',
            self::SHADCN_CARD => 'shadcn-card',
            self::TIPTAP => 'tiptap',
            self::HERO => 'hero',
            self::SPOTIFY_EMBED => 'spotify-embed',
            self::SOCIAL_EMBED => 'social-embed',
            self::FLOW_GRAPH => 'flow-graph',
            self::NUMBER_STAT_SECTION => 'number-stat-section',
            self::NEWS => 'news',
            self::CALENDAR => 'calendar',
            self::CONTENT_GRID => 'content-grid',
            self::TEXT_BOX => 'text-box',
            self::CAROUSEL_SLIDE_DECK => 'carousel-slide-deck',
            self::HERO_CAROUSEL => 'hero-carousel',
            self::CARD_STACK => 'card-stack',
            self::PHOTO_GALLERY => 'photo-gallery',
            self::LINK_LIST => 'link-list',
            self::EVENT_LIST => 'event-list',
            self::PERSON_QUOTE => 'person-quote',
            self::SECTION => 'section',
            self::SPACER => 'spacer',
            self::TIMETABLE => 'timetable',
        };
    }
}
