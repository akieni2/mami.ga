import 'package:flutter/material.dart';

/// Services MAMI Super App.
enum MamiServiceModule {
  taxi('taxi', 'Taxi', 'Commander une course', Icons.local_taxi),
  carpool('carpool', 'Covoiturage', 'Partager un trajet', Icons.people),
  transport('transport', 'Transport', 'Marchandises & fret', Icons.local_shipping),
  commerce('commerce', 'Commerce', 'Annuaire PME', Icons.storefront),
  municipality('municipality', 'Mairie', 'Services municipaux', Icons.account_balance);

  const MamiServiceModule(this.slug, this.title, this.subtitle, this.icon);

  final String slug;
  final String title;
  final String subtitle;
  final IconData icon;
}
