import 'package:flutter/material.dart';

/// Displays Buddhist Era years while keeping [DateTime] values Gregorian.
class BuddhistCalendarDelegate extends GregorianCalendarDelegate {
  const BuddhistCalendarDelegate();

  static const int yearOffset = 543;

  String _replaceYear(
    String value,
    DateTime date,
    MaterialLocalizations localizations,
  ) {
    final gregorianYear = localizations.formatYear(date);
    final buddhistYear = localizations.formatYear(
      DateTime(date.year + yearOffset),
    );
    return value.replaceAll(gregorianYear, buddhistYear);
  }

  @override
  String formatMonthYear(DateTime date, MaterialLocalizations localizations) =>
      _replaceYear(localizations.formatMonthYear(date), date, localizations);

  @override
  String formatYear(int year, MaterialLocalizations localizations) =>
      localizations.formatYear(DateTime(year + yearOffset));

  @override
  String formatShortDate(DateTime date, MaterialLocalizations localizations) =>
      _replaceYear(localizations.formatShortDate(date), date, localizations);

  @override
  String formatFullDate(DateTime date, MaterialLocalizations localizations) =>
      _replaceYear(localizations.formatFullDate(date), date, localizations);

  @override
  String formatCompactDate(
    DateTime date,
    MaterialLocalizations localizations,
  ) => _replaceYear(localizations.formatCompactDate(date), date, localizations);

  @override
  DateTime? parseCompactDate(
    String? inputString,
    MaterialLocalizations localizations,
  ) {
    final parsed = localizations.parseCompactDate(inputString);
    if (parsed == null) return null;
    final year = parsed.year >= 2400 ? parsed.year - yearOffset : parsed.year;
    return DateTime(year, parsed.month, parsed.day);
  }

  @override
  String dateHelpText(MaterialLocalizations localizations) =>
      '${localizations.dateHelpText} (พ.ศ.)';
}
