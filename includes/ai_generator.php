<?php
/**
 * SoulMate AI-Simulation Generator (Dual-Language Emotional Logic)
 * -------------------------------------------------------------
 * This engine synthesizes emotional shayari based on TWO vectors:
 * 1. The User's Current Mood (Happy, Sad, Calm, etc.)
 * 2. The Selected/Detected Language (English, Hindi, Marathi)
 */

class SoulMateAI {
    
    // Multi-Language Structural Pools
    private static $pools = [
        'English' => [
            'starters' => [
                'happy' => ['Life is', 'Everything', 'The sun', 'My heart', 'Today', 'Every step'],
                'sad' => ['Sometimes', 'Silence', 'The rain', 'Lonely but', 'Deep in', 'The night'],
                'calm' => ['Peace is', 'Stillness', 'Breathe in', 'Slowly', 'The soul', 'Calmness'],
                'romantic' => ['Every beat', 'Your eyes', 'Two souls', 'Always', 'In love', 'Forever'],
                'angry' => ['The fire', 'Strength', 'Unbroken', 'Power', 'Rising', 'Enough is']
            ],
            'emotions' => [
                'happy' => ['magic', 'sunshine', 'dreams', 'joy', 'smile', 'bright'],
                'sad' => ['darkness', 'tears', 'hollow', 'void', 'fragile', 'pain'],
                'calm' => ['ocean', 'sukoon', 'breeze', 'still', 'zen', 'quiet'],
                'romantic' => ['dhadkan', 'heartbeat', 'magical', 'close', 'destiny', 'spark'],
                'angry' => ['storm', 'energy', 'force', 'warrior', 'limit', 'will']
            ],
            'endings' => [
                'happy' => ['is all we need. ✨', 'makes it perfect.', 'is everywhere. 😊', 'shines today.'],
                'sad' => ['will heal eventually. 🕊️', 'this too shall pass.', 'pan hope aahe. 🌙', 'needs time.'],
                'calm' => ['is real power. 🧘', 'the stars are watching.', 'just breathe deep.', 'find your zen.'],
                'romantic' => ['eternally mine. ❤️', 'infinite love stories.', 'is my life. 🌹', 'shabd nahiye.'],
                'angry' => ['never back down. 🔥', 'ready to roar.', 'is my motivation.', 'stay strong. 💪']
            ]
        ],
        'Hindi' => [
            'starters' => [
                'happy' => ['Zindagi', 'Aaj toh', 'Mausam', 'Dil mera', 'Khushi', 'Har pal'],
                'sad' => ['Kabhi kabhi', 'Tanhai mein', 'Dil mera', 'Andhera', 'Dard', 'Yaadein'],
                'calm' => ['Sukoon', 'Shanti', 'Sannata', 'Dheere se', 'Man mera', 'Thairaav'],
                'romantic' => ['Dhadkan', 'Teri aankhein', 'Mohabbat', 'Do dil', 'Pyaar', 'Humesha'],
                'angry' => ['Aag', 'Takat', 'Junoon', 'Ab bas', 'Hausla', 'Toofan']
            ],
            'emotions' => [
                'happy' => ['muskurahat', 'sapne', 'jaadu', 'roshni', 'bahar', 'masti'],
                'sad' => ['aansu', 'khoya', 'tanha', 'veeran', 'gham', 'raat'],
                'calm' => ['thanda', 'hawa', 'chain', 'itminan', 'thehra', 'noor'],
                'romantic' => ['soulmate', 'ishq', 'dhadkan', 'jashn', 'pass', 'nazar'],
                'angry' => ['shakti', 'garjan', 'mashaal', 'toofan', 'Aag', 'zor']
            ],
            'endings' => [
                'happy' => ['zindagi gulzar hai! ✨', 'sab theek hai. 😊', 'chamakti rahegi.', 'saath hai.'],
                'sad' => ['sab theek ho jayega. 🌙', 'waqt lagega.', 'umeed mat chhodna.', 'ek naye savere ke liye.'],
                'calm' => ['shukriya kaho. 🧘', 'sitare dekh rahe hain.', 'saans lo.', 'hi asli taaqat hai.'],
                'romantic' => ['tujh mein basi hai. ❤️', 'humesha ke liye.', 'meri duniya hai.', 'kehte hain.'],
                'angry' => ['kabhi mat rukna. 🔥', 'ladne ke liye taiyaar.', 'meri himmat hai.', 'khud pe yakeen rakho. 💪']
            ]
        ],
        'Marathi' => [
            'starters' => [
                'happy' => ['Ayushya', 'Aaj cha', 'Man maze', 'Swapna', 'Hasa', 'Pratek kshan'],
                'sad' => ['Kadhi kadhi', 'Ektekona', 'Dukh', 'Andhari', 'Radu', 'Athvani'],
                'calm' => ['Sukoon', 'Shantata', 'Thehrav', 'Holu holu', 'Manatle', 'Sannata'],
                'romantic' => ['Dhadkan', 'Tujhya dolyat', 'Prem', 'Don jeev', 'Sobat', 'Kayam'],
                'angry' => ['Aag', 'Shakti', 'Zidd', 'Aata bas', 'Samarthya', 'Wadal']
            ],
            'emotions' => [
                'happy' => ['muskurahat', 'aanand', 'prakash', 'magical', 'hasu', 'masti'],
                'sad' => ['aasu', 'ekla', 'viraan', 'andhar', 'vedna', 'raat'],
                'calm' => ['thanda', 'wara', 'shanti', 'sukoon', 'thehrao', 'noor'],
                'romantic' => ['jivlag', 'prema', 'dhadkan', 'jashn', 'sobat', 'najar'],
                'angry' => ['takat', 'garjna', 'mashaal', 'toofan', 'shakti', 'zor']
            ],
            'endings' => [
                'happy' => ['sunder aahe! ✨', 'sagla changla hoil. 😊', 'chamkat rahila.', 'sobat aahe.'],
                'sad' => ['punha ujed yeil. 🌙', 'vel lagel.', 'hope sodu nako.', 'navin suruvat hoil.'],
                'calm' => ['asli power aahe. 🧘', 'chandne baghat aahet.', 'shvas ghe.', 'hi shakti aahe.'],
                'romantic' => ['tujhyat basle aahe. ❤️', 'kayam sathi.', 'majhi duniya.', 'shabd nahiye.'],
                'angry' => ['kadhich thambu nako. 🔥', 'jagayla taiyaar.', 'majhi himmat.', 'swatahvar vishwas thev. 💪']
            ]
        ]
    ];

    /**
     * Synthesizes a unique emotional quote in the specified language
     */
    public static function generate($mood = 'happy', $lang = 'English') {
        $mood = strtolower($mood);
        if (!isset(self::$pools['English']['starters'][$mood])) $mood = 'happy';
        
        $lang = $lang ?? 'English';
        if (!isset(self::$pools[$lang])) $lang = 'English';

        $data = self::$pools[$lang];

        $s = $data['starters'][$mood][array_rand($data['starters'][$mood])];
        $e = $data['emotions'][$mood][array_rand($data['emotions'][$mood])];
        $end = $data['endings'][$mood][array_rand($data['endings'][$mood])];

        return "$s $e $end";
    }
}
?>
