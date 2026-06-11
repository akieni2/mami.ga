import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class PriceInputField extends StatelessWidget {
  const PriceInputField({
    super.key,
    required this.controller,
    this.suggestedPrice,
  });

  final TextEditingController controller;
  final double? suggestedPrice;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextFormField(
          controller: controller,
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: const InputDecoration(
            labelText: 'Prix proposé (FCFA)',
            prefixIcon: Icon(Icons.payments_outlined),
            border: OutlineInputBorder(),
            hintText: 'Ex. 3000',
          ),
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Prix requis';
            }
            final amount = int.tryParse(value);
            if (amount == null || amount < 500) {
              return 'Minimum 500 FCFA';
            }
            if (amount > 500000) {
              return 'Maximum 500 000 FCFA';
            }
            return null;
          },
        ),
        if (suggestedPrice != null) ...[
          const SizedBox(height: 6),
          Text(
            'Prix conseillé : ${suggestedPrice!.toStringAsFixed(0)} FCFA',
            style: TextStyle(fontSize: 13, color: Colors.grey.shade600),
          ),
        ],
      ],
    );
  }
}
