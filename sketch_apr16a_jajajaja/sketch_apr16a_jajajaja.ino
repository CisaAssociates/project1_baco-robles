#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <SPI.h>
#include <MFRC522.h>

#define RST_PIN D3
#define SS_PIN  D4

const char* ssid = "Bawal connect";
const char* password = "paloaduy";
const char* serverIP = "192.168.254.182";  // Flask server IP
const int serverPort = 5000;           // ✅ Corrected to match Flask server port

MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();

  WiFi.begin(ssid, password);
  Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\n✅ WiFi connected!");
  Serial.println("🔄 Waiting for RFID card...");
}

void send_uid(const String& endpoint, const String& uid) {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;

    String url = "http://" + String(serverIP) + ":" + String(serverPort) + "/" + endpoint + "?uid=" + uid;
    Serial.println("🌐 Sending to: " + url);
    http.begin(client, url);

    int httpCode = http.GET();
    if (httpCode > 0) {
      String payload = http.getString();
      Serial.println("📬 [" + endpoint + "] Response: " + payload);
    } else {
      Serial.println("❌ [" + endpoint + "] HTTP error: " + http.errorToString(httpCode));
    }

    http.end();
  } else {
    Serial.println("⚠️ WiFi not connected");
  }
}

void loop() {
  if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();

  Serial.print("🔐 UID detected: ");
  Serial.println(uid);

  send_uid("receive_uid", uid);      // Register
  delay(500);                        // Optional delay
  send_uid("check_status", uid);     // Attendance
  delay(500);                        // Optional delay
  send_uid("check_gatepass", uid);   // ✅ Gatepass (NEW)

  rfid.PICC_HaltA();
  delay(2000);  // Prevent rapid repeat scans
}
