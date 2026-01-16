//ESP-32-S3
#include <TinyGPSPlus.h>
#include <WiFi.h>
#include <HTTPClient.h>

TinyGPSPlus gps;
HardwareSerial gpsSerial(1);

#define GPS_RX 16   
#define GPS_TX 17  

const char* ssid     = "WIFI_SSID";
const char* password = "WIFI_PASS";

const char* serverURL = "https://SERVER_IP/pr/api/gps_update.php";

#define ROUTE_ID 4

unsigned long lastSend = 0;
const unsigned long sendInterval = 2000; 

void setup() {
  Serial.begin(115200);

  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);

  Serial.println("\nESP32-S3 GPS LIVE TRACKING (Route 4)");

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi Connected");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  while (gpsSerial.available()) {
    gps.encode(gpsSerial.read());
  }

  if (gps.location.isUpdated() && millis() - lastSend > sendInterval) {
    lastSend = millis();
    sendGPSData();
  }

  if (millis() > 5000 && gps.charsProcessed() < 10) {
    Serial.println("⚠ No GPS detected");
    delay(2000);
  }
}

void sendGPSData() {

  if (!gps.location.isValid()) {
    Serial.println("Location not valid yet");
    return;
  }

  double lat = gps.location.lat();
  double lng = gps.location.lng();
  double speed = gps.speed.kmph();

  Serial.println("------ GPS DATA ------");
  Serial.print("Latitude  : "); Serial.println(lat, 6);
  Serial.print("Longitude : "); Serial.println(lng, 6);
  Serial.print("Speed kmh : "); Serial.println(speed);
  Serial.println("----------------------");

  if (WiFi.status() == WL_CONNECTED) {

    HTTPClient http;

    String url = String(serverURL) +
                 "?route=" + String(ROUTE_ID) +
                 "&lat=" + String(lat, 6) +
                 "&lng=" + String(lng, 6) +
                 "&speed=" + String(speed, 2);

    http.begin(url);
    int httpCode = http.GET();
    http.end();

    Serial.print("HTTP Response Code: ");
    Serial.println(httpCode);

  } else {
    Serial.println("WiFi disconnected");
  }
}
