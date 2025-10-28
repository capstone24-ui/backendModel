import sys
sys.stdout.reconfigure(encoding='utf-8')

import numpy as np
import os
import json
from tensorflow.keras.models import load_model
from tensorflow.keras.preprocessing.image import load_img, img_to_array

model = load_model("corn_disease_models_updated.keras")

class_names = ['Blight', 'Common Rust', 'Gray Leaf Spot', 'Healthy', 'Unknown']

target_size = (224, 224)

def preprocess_image(image_path):
    img = load_img(image_path, target_size=target_size)
    img_array = img_to_array(img)
    img_array = img_array / 255.0 
    img_array = np.expand_dims(img_array, axis=0)
    return img_array

def main():
    if len(sys.argv) != 2:
        print(json.dumps({"error": "Image path not provided"}))
        sys.exit(1)

    image_path = sys.argv[1]

    if not os.path.exists(image_path):
        print(json.dumps({"error": f"File '{image_path}' not found"}))
        sys.exit(1)

    image = preprocess_image(image_path)
    prediction = model.predict(image)
    predicted_class = class_names[np.argmax(prediction)]
    confidence = float(np.max(prediction) * 100)

    result = {
        "prediction": predicted_class,
        "confidence": round(confidence, 2)
    }

    print(json.dumps(result, ensure_ascii=False))

if __name__ == "__main__":
    main()
