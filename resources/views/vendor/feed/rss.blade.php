<?=
    /* Using an echo tag here so the `<? ... ?>` won't get parsed as short tags */
    '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <atom:link href="{{ url($meta['link']) }}" rel="self" type="application/rss+xml" />
        <title>{!! \Spatie\Feed\Helpers\Cdata::out($meta['title']) !!}</title>
        <link>{!! \Spatie\Feed\Helpers\Cdata::out(url('/')) !!}</link>
@if(!empty($meta['image']))
        <image>
            <url>{{ url($meta['image']) }}</url>
            <title>{!! \Spatie\Feed\Helpers\Cdata::out($meta['title']) !!}</title>
            <link>{!! \Spatie\Feed\Helpers\Cdata::out(url('/')) !!}</link>
        </image>
@endif
        <description>{!! \Spatie\Feed\Helpers\Cdata::out($meta['description']) !!}</description>
        <language>{{ $meta['language'] }}</language>
        <pubDate>{{ $meta['updated'] }}</pubDate>
        <lastBuildDate>{{ $meta['updated'] }}</lastBuildDate>

        @foreach($items as $item)
            <item>
                <title>{!! \Spatie\Feed\Helpers\Cdata::out($item->title) !!}</title>
                <link>{{ url($item->link) }}</link>
                <guid isPermaLink="true">{{ url($item->link) }}</guid>
                <description>{!! \Spatie\Feed\Helpers\Cdata::out($item->summary) !!}</description>
@if($item->__isset('content'))
                <content:encoded>{!! \Spatie\Feed\Helpers\Cdata::out($item->content) !!}</content:encoded>
@endif
@if($item->__isset('enclosure'))
                <enclosure url="{{ url($item->enclosure) }}" length="{{ $item->enclosureLength }}" type="{{ $item->enclosureType }}" />
@endif
@if($item->__isset('image'))
                <media:content url="{{ url($item->image) }}" medium="image" />
                <media:thumbnail url="{{ url($item->image) }}" />
@endif
                <author>{!! \Spatie\Feed\Helpers\Cdata::out($item->authorName.(empty($item->authorEmail) ? '' : ' <'.$item->authorEmail.'>')) !!}</author>
                <pubDate>{{ $item->timestamp() }}</pubDate>
@foreach($item->category as $category)
                <category>{{ $category }}</category>
@endforeach
@foreach($item->alternates ?? [] as $alternate)
                <atom:link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}" />
@endforeach
            </item>
        @endforeach
    </channel>
</rss>
