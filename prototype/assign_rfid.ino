#include <WiFi.h>
#include <HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <ArduinoJson.h>

const char* ssid     = "WIFI_SSID";
const char* password = "WIFI_PASS";

const char* scanURL   = "http://SERVER_IP/pr/api/rfid_scan.php";
const char* stateURL  = "http://SERVER_IP/pr/api/assign_state.php";
const char* assignURL = "http://SERVER_IP/pr/api/assign_uid.php";

#define SS_PIN   10
#define RST_PIN  9
#define LED_PIN  2

MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);

  SPI.begin(12, 13, 11, SS_PIN);
  rfid.PCD_Init();

  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi Connected");
  Serial.print("ESP32 IP: ");
  Serial.println(WiFi.localIP());
  Serial.println("Ready. Scan RFID card...");
}

void loop() {

  if (!rfid.PICC_IsNewCardPresent()) return;
  if (!rfid.PICC_ReadCardSerial()) return;

  String uid = getUIDString();
  Serial.print("Card UID: ");
  Serial.println(uid);

  int targetUser = -1;

  if (checkAssignMode(targetUser)) {
    Serial.print("ASSIGN MODE → User ID: ");
    Serial.println(targetUser);
    assignUID(uid, targetUser);
  } else {
    normalScan(uid);
  }

  rfid.PICC_HaltA();
  delay(1500);
}

String getUIDString() {
  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) uid += "0";
    uid += String(rfid.uid.uidByte[i], HEX);
    if (i < rfid.uid.size - 1) uid += ":";
  }
  uid.toUpperCase();
  return uid;
}

bool checkAssignMode(int &userId) {
  HTTPClient http;
  http.begin(stateURL);

  int code = http.GET();
  if (code != 200) {
    http.end();
    return false;
  }

  String payload = http.getString();
  http.end();

  StaticJsonDocument<128> doc;
  if (deserializeJson(doc, payload)) return false;

  if (!doc["active"]) return false;

  userId = doc["user_id"];
  return true;
}

void assignUID(String uid, int userId) {
  HTTPClient http;
  http.begin(assignURL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String postData = "uid=" + uid + "&user_id=" + String(userId);
  int code = http.POST(postData);

  http.end();

  if (code == 200) {
    Serial.println("UID ASSIGNED SUCCESSFULLY");
    digitalWrite(LED_PIN, HIGH);
    delay(1000);
    digitalWrite(LED_PIN, LOW);
  } else {
    Serial.println("ASSIGN FAILED");
  }
}

void normalScan(String uid) {

  HTTPClient http;
  http.begin(scanURL);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  int httpCode = http.POST("uid=" + uid);
  String payload = http.getString();
  http.end();

  Serial.print("HTTP Code: ");
  Serial.println(httpCode);
  Serial.print("Response: ");
  Serial.println(payload);

  StaticJsonDocument<256> doc;
  if (deserializeJson(doc, payload)) {
    Serial.println("JSON parse failed");
    digitalWrite(LED_PIN, LOW);
    return;
  }

  if (doc["success"]) {
    Serial.println("ACCESS GRANTED");
    Serial.print("User: ");
    Serial.println(doc["name"].as<String>());
    Serial.print("Credits: ");
    Serial.println(doc["credits"].as<float>());
    digitalWrite(LED_PIN, HIGH);
  } else {
    Serial.println("ACCESS DENIED");
    Serial.println(doc["message"].as<String>());
    digitalWrite(LED_PIN, LOW);
  }
}
