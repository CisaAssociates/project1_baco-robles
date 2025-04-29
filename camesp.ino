#include <dummy.h>

#include "esp_camera.h"
#include <WiFi.h>
#include <WebServer.h>

// Replace with your network credentials
const char* ssid = "Bawal connect";
const char* password = "paloaduy";

// Define camera model pins (for AI Thinker ESP32-CAM)
#define CAMERA_MODEL_AI_THINKER

#include "camera_pins.h"  // Default camera pin configuration for AI Thinker ESP32-CAM

WebServer server(80);

// Set up camera settings
void startCameraServer() {
  server.on("/", HTTP_GET, [](WebServer &server) {
    WiFiClient client = server.client();
    Serial.println("Client connected!");
    streamVideo(client);
  });

  server.begin();
  Serial.println("HTTP server started");
}

// Stream video function
void streamVideo(WiFiClient client) {
  camera_fb_t * fb = NULL;
  while (client.connected()) {
    fb = esp_camera_fb_get();
    if (!fb) {
      Serial.println("Camera capture failed");
      return;
    }
    client.write(fb->buf, fb->len);
    esp_camera_fb_return(fb);
  }
}

// WiFi connection setup
void connectToWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.print(".");
  }

  Serial.println("Connected to WiFi");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
}

void setup() {
  Serial.begin(115200);

  // Initialize camera
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = 16;
  config.pin_d1 = 17;
  config.pin_d2 = 18;
  config.pin_d3 = 19;
  config.pin_d4 = 21;
  config.pin_d5 = 22;
  config.pin_d6 = 23;
  config.pin_d7 = 36;
  config.pin_xclk = 0;
  config.pin_pclk = 22;
  config.pin_vsync = 25;
  config.pin_href = 23;
  config.pin_siod = 26;
  config.pin_sioc = 27;
  config.pin_reset = -1;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;

  // Initialize camera
  if (esp_camera_init(&config) != ESP_OK) {
    Serial.println("Camera initialization failed");
    return;
  }

  connectToWiFi();  // Connect to Wi-Fi
  startCameraServer();  // Start HTTP server to stream video
}

void loop() {
  server.handleClient();  // Handle HTTP requests
}
