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

disease_info = {
    'Blight': {
        'symptoms': [
            "Water-soaked lesions on leaves",
            "Brown or tan streaks",
            "Leaf blighting and wilting"
        ],
        'remedies': [
            "Use blight-resistant seeds",
            "Apply appropriate fungicide",
            "Remove and destroy infected plants"
        ]
    },
    'Common Rust': {
        'symptoms': [
            "Reddish-brown pustules on leaves",
            "Yellow spots that turn brown",
            "Reduced photosynthesis"
        ],
        'remedies': [
            "Plant rust-resistant hybrids",
            "Use crop rotation",
            "Apply fungicides like Mancozeb"
        ]
    },
    'Gray Leaf Spot': {
        'symptoms': [
            "Rectangular gray to tan lesions",
            "Leaf tissue death",
            "Early leaf drop"
        ],
        'remedies': [
            "Improve air circulation between plants",
            "Use resistant corn varieties",
            "Apply fungicides at early stage"
        ]
    },
    'Healthy': {
        'symptoms': ["No visible disease symptoms"],
        'remedies': ["Maintain good agricultural practices"]
    },
    'Unknown': {
        'symptoms': ["Unrecognized pattern in image"],
        'remedies': ["Retake image or consult an expert"]
    }
}

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

    info = disease_info.get(predicted_class, {
        "symptoms": ["No data available"],
        "remedies": ["Consult an agronomist"]
    })

    result = {
        "prediction": predicted_class,
        "confidence": round(confidence, 2),
        "symptoms": info["symptoms"],
        "remedies": info["remedies"]
    }

    print(json.dumps(result, ensure_ascii=False))

if __name__ == "__main__":
    main()