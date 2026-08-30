import 'package:flutter/material.dart';
import 'package:healthicons_flutter/healthicons_flutter.dart';

import '../../core/utils/app_icon_mapper.dart';

class SymptomIcon extends StatelessWidget {
  const SymptomIcon({
    super.key,
    required this.iconName,
    this.size = 24,
    this.color,
  });

  final String? iconName;
  final double size;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final value = iconName?.trim() ?? '';
    if (!value.startsWith('health:')) {
      return Icon(symptomIconFromName(value), size: size, color: color);
    }

    return switch (value.substring(7)) {
      'Allergies' => AllergiesOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'AutoimmuneDisease' => AutoimmuneDiseaseOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'BackPain' => BackPainOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Chills' => ChillsOutline24px(color: color, width: size, height: size),
      'CoughingAlt' => CoughingAltOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Deaf' => DeafOutline24px(color: color, width: size, height: size),
      'Diarrhea' => DiarrheaOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Headache' => HeadacheOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'IntestinalPain' => IntestinalPainOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'LowVision' => LowVisionOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Overweight' => OverweightOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Pneumonia' => PneumoniaOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Sweating' => SweatingOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Underweight' => UnderweightOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Vomiting' => VomitingOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Arm' => ArmOutline24px(color: color, width: size, height: size),
      'Bladder' => BladderOutline24px(color: color, width: size, height: size),
      'BloodCells' => BloodCellsOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'BloodDrop' => BloodDropOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Body' => BodyOutline24px(color: color, width: size, height: size),
      'CellNuclei' => CellNucleiOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Dna' => DnaOutline24px(color: color, width: size, height: size),
      'Ear' => EarOutline24px(color: color, width: size, height: size),
      'Eye' => EyeOutline24px(color: color, width: size, height: size),
      'Foot' => FootOutline24px(color: color, width: size, height: size),
      'Gallbladder' => GallbladderOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'HeartOrgan' => HeartOrganOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Joints' => JointsOutline24px(color: color, width: size, height: size),
      'Kidneys' => KidneysOutline24px(color: color, width: size, height: size),
      'Leg' => LegOutline24px(color: color, width: size, height: size),
      'Liver' => LiverOutline24px(color: color, width: size, height: size),
      'Lungs' => LungsOutline24px(color: color, width: size, height: size),
      'Mouth' => MouthOutline24px(color: color, width: size, height: size),
      'Neurology' => NeurologyOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Nose' => NoseOutline24px(color: color, width: size, height: size),
      'Pancreas' => PancreasOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Skeleton' => SkeletonOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Skull' => SkullOutline24px(color: color, width: size, height: size),
      'Spine' => SpineOutline24px(color: color, width: size, height: size),
      'Spleen' => SpleenOutline24px(color: color, width: size, height: size),
      'Stomach' => StomachOutline24px(color: color, width: size, height: size),
      'Tissue' => TissueOutline24px(color: color, width: size, height: size),
      'Tooth' => ToothOutline24px(color: color, width: size, height: size),
      'Tumour' => TumourOutline24px(color: color, width: size, height: size),
      'BloodPressureMonitor' => BloodPressureMonitorOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'DiabetesMeasure' => DiabetesMeasureOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Fever' => FeverOutline24px(color: color, width: size, height: size),
      'Stethoscope' => StethoscopeOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'ThermometerDigital' => ThermometerDigitalOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'OxygenTank' => OxygenTankOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Ventilator' => VentilatorOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Xray' => XrayOutline24px(color: color, width: size, height: size),
      'Coughing' => CoughingOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Crying' => CryingOutline24px(color: color, width: size, height: size),
      'Dizzy' => DizzyOutline24px(color: color, width: size, height: size),
      'Expectorate' => ExpectorateOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'FeverEmotions' => FeverEmotionsOutline24pxEmotions(
        color: color,
        width: size,
        height: size,
      ),
      'Nauseous' => NauseousOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Sleepy' => SleepyOutline24px(color: color, width: size, height: size),
      'SweatingEmotions' => SweatingEmotionsOutline24pxEmotions(
        color: color,
        width: size,
        height: size,
      ),
      'Tongue' => TongueOutline24px(color: color, width: size, height: size),
      'Woozy' => WoozyOutline24px(color: color, width: size, height: size),
      'Cardiogram' => CardiogramOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Heartbeat' => HeartbeatOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'MentalHealth' => MentalHealthOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Poison' => PoisonOutline24px(color: color, width: size, height: size),
      'Smoking' => SmokingOutline24px(color: color, width: size, height: size),
      'Virus' => VirusOutline24px(color: color, width: size, height: size),
      'Symptom' => SymptomOutline24px(color: color, width: size, height: size),
      'Antibody' => AntibodyOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Enzyme' => EnzymeOutline24px(color: color, width: size, height: size),
      'FemaleReproductiveSystem' => FemaleReproductiveSystemOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'PenisAlt' => PenisAltOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Prostate' => ProstateOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'VaginaAlt' => VaginaAltOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'CervicalCancer' => CervicalCancerOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Gonorrhea' => GonorrheaOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Ribbon' => RibbonOutline24px(color: color, width: size, height: size),
      'Tb' => TbOutline24px(color: color, width: size, height: size),
      'Vih' => VihOutline24px(color: color, width: size, height: size),
      'Angry' => AngryOutline24px(color: color, width: size, height: size),
      'Bandaged' => BandagedOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Calm' => CalmOutline24px(color: color, width: size, height: size),
      'Confused' => ConfusedOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Happy' => HappyOutline24px(color: color, width: size, height: size),
      'LoudlyCrying' => LoudlyCryingOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Masked' => MaskedOutline24px(color: color, width: size, height: size),
      'Measles' => MeaslesOutline24px(color: color, width: size, height: size),
      'Nervous' => NervousOutline24px(color: color, width: size, height: size),
      'Neutral' => NeutralOutline24px(color: color, width: size, height: size),
      'NotOk' => NotOkOutline24px(color: color, width: size, height: size),
      'Ok' => OkOutline24px(color: color, width: size, height: size),
      'Sad' => SadOutline24px(color: color, width: size, height: size),
      'ContactLenses' => ContactLensesOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'CpapMachine' => CpapMachineOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'CpapMasks' => CpapMasksOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'CpapTubes' => CpapTubesOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Defibrillator' => DefibrillatorOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Ecmo' => EcmoOutline24px(color: color, width: size, height: size),
      'Hospitalized' => HospitalizedOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Inpatient' => InpatientOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'InfusionPump' => InfusionPumpOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'IntravenousBag' => IntravenousBagOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Llin' => LlinOutline24px(color: color, width: size, height: size),
      'Microscope' => MicroscopeOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Observation' => ObservationOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Staples' => StaplesOutline24px(color: color, width: size, height: size),
      'Stitches' => StitchesOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Syringe' => SyringeOutline24px(color: color, width: size, height: size),
      'TestTubes' => TestTubesOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Wheelchair' => WheelchairOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'WheelchairAlt' => WheelchairAltOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Biopsy' => BiopsyOutline24px(color: color, width: size, height: size),
      'Diabetes' => DiabetesOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Doctor' => DoctorOutline24px(color: color, width: size, height: size),
      'HealthWorker' => HealthWorkerOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Pregnant' => PregnantOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'WaterSanitation' => WaterSanitationOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Hospital' => HospitalOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'EmergencyPost' => EmergencyPostOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Respirator' => RespiratorOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Biomarker' => BiomarkerOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Death' => DeathOutline24px(color: color, width: size, height: size),
      'Diagnostics' => DiagnosticsOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Hazardous' => HazardousOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Health' => HealthOutline24px(color: color, width: size, height: size),
      'Heart' => HeartOutline24px(color: color, width: size, height: size),
      'HeartCardiogram' => HeartCardiogramOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'HomeQuarantine' => HomeQuarantineOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Hospice' => HospiceOutline24px(color: color, width: size, height: size),
      'Outbreak' => OutbreakOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'PalliativeCare' => PalliativeCareOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Pharmacy' => PharmacyOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'RiskAnalysis' => RiskAnalysisOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'SmokingCessation' => SmokingCessationOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'VirusAlt' => VirusAltOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Ambulance' => AmbulanceOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Mosquito' => MosquitoOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Medicines' => MedicinesOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Pill1' => Pill1Outline24px(color: color, width: size, height: size),
      'Pills2' => Pills2Outline24px(color: color, width: size, height: size),
      'Pills3' => Pills3Outline24px(color: color, width: size, height: size),
      'Pills4' => Pills4Outline24px(color: color, width: size, height: size),
      'Nutrition' => NutritionOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'SugarFree' => SugarFreeOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'UnhealthyFood' => UnhealthyFoodOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'RunningWater' => RunningWaterOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Walking' => WalkingOutline24px(color: color, width: size, height: size),
      'WalkSupported' => WalkSupportedOutline24px(
        color: color,
        width: size,
        height: size,
      ),
      'Running' => RunningOutline24px(color: color, width: size, height: size),
      _ => Icon(Icons.monitor_heart_outlined, size: size, color: color),
    };
  }
}
