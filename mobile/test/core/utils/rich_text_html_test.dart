import 'package:checkup/core/utils/rich_text_html.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('unwraps a Markdown link accidentally stored in an image src', () {
    const url =
        'https://example.supabase.co/storage/v1/object/public/uploads/articles/photo.webp';
    const html = '<img class="rounded" src="[$url]($url)">';

    expect(
      RichTextHtml.resolveMediaUrls(html),
      '<img class="rounded" src="$url">',
    );
  });

  test('keeps an external Supabase storage URL unchanged', () {
    const url =
        'https://nwumzqetxpglrcinlcni.supabase.co/storage/v1/object/public/uploads/articles/photo.webp';
    const html = '<img src="$url">';

    expect(RichTextHtml.resolveMediaUrls(html), html);
  });

  test('resolves only application storage folders through the media API', () {
    const html = '<img src="/storage/notifications/photo.webp">';
    final result = RichTextHtml.resolveMediaUrls(html);

    expect(result, contains('/api/media/notifications/photo.webp'));
  });

  test('unwraps a Supabase image when the Markdown label is escaped', () {
    const url =
        'https://nwumzqetxpglrcinlcni.supabase.co/storage/v1/object/public/uploads/first_aids/8baec8a0-0a75-4143-9578-49b03a632ebe.webp';
    const html =
        r'<p></p><img class="rounded-lg max-w-full" src="[https://nwumzqetxpglrcinlcni.supabase.co/storage/v1/object/public/uploads/first\_aids/8baec8a0-0a75-4143-9578-49b03a632ebe.webp](https://nwumzqetxpglrcinlcni.supabase.co/storage/v1/object/public/uploads/first_aids/8baec8a0-0a75-4143-9578-49b03a632ebe.webp)" data-width-percent="100">';

    expect(
      RichTextHtml.resolveMediaUrls(html),
      '<p></p><img class="rounded-lg max-w-full" src="$url" data-width-percent="100">',
    );
  });
}
