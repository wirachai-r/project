class TreatmentOrderModel {
  final String orderId;
  final String orderName;
  final String? orderNameEn;
  final String? description;
  final String urgencyType; // R, P, Y, G, W
  final int orderSequence;

  TreatmentOrderModel({
    required this.orderId,
    required this.orderName,
    this.orderNameEn,
    this.description,
    required this.urgencyType,
    required this.orderSequence,
  });

  factory TreatmentOrderModel.fromJson(Map<String, dynamic> json) {
    return TreatmentOrderModel(
      orderId: json['order_id'] ?? '',
      orderName: json['order_name'] ?? '',
      orderNameEn: json['order_name_en'],
      description: json['description'],
      urgencyType: json['urgency_type'] ?? 'G',
      orderSequence: json['order_sequence'] ?? 0,
    );
  }
}
