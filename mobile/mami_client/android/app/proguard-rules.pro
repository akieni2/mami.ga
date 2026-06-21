# pusher-java-client (Reverb) — SLF4J binding optionnel en release R8
-dontwarn org.slf4j.**
-dontwarn org.slf4j.impl.StaticLoggerBinder

# mobile_scanner / CameraX / ML Kit — évite NPE R8 en APK release (Sprint 3.2.2)
-keep class androidx.camera.** { *; }
-keep class com.google.mlkit.** { *; }
-keep class com.google.android.gms.** { *; }
-keep public class androidx.camera.core.impl.CameraCaptureMetaData$** { *; }
-dontwarn androidx.camera.**
-dontwarn com.google.mlkit.**
