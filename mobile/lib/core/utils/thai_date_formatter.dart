const thaiAbbreviatedMonths = [
  'ม.ค.',
  'ก.พ.',
  'มี.ค.',
  'เม.ย.',
  'พ.ค.',
  'มิ.ย.',
  'ก.ค.',
  'ส.ค.',
  'ก.ย.',
  'ต.ค.',
  'พ.ย.',
  'ธ.ค.',
];

String formatThaiDate(DateTime date) =>
    '${date.day} ${thaiAbbreviatedMonths[date.month - 1]} ${date.year + 543}';

String formatShortThaiDate(DateTime date) =>
    '${date.day} ${thaiAbbreviatedMonths[date.month - 1]} '
    '${(date.year + 543).toString().substring(2)}';

String formatThaiDateTime(DateTime date) {
  final hour = date.hour.toString().padLeft(2, '0');
  final minute = date.minute.toString().padLeft(2, '0');
  return '${formatThaiDate(date)} $hour:$minute น.';
}
