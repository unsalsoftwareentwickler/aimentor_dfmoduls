<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// OPENAI API KEY BURAYA KOYULMALI
$apiKey = '';

session_start();
if (!isset($_SESSION['conversation'])) {
    $_SESSION['conversation'] = [
        ['role' => 'system', 'content' => 'Sen Mentor AI adında bir elearning asistanısın ve eğitimin başlangıcısındasın
eğitim konusuda aşağıda bu konunun dışına çıkmadan, kullanıcıdan "Bu eğitimden ne bekliyorsun?" sorusuna kısa bir yanıt aldıktan sonra aşağıdaki konuyu özetle anlat:
                
Egitim Metni Başı:
Hiç düşündünüz mü? Çoğu zaman bize yapılan geri bildirimler geçmişteki hatalarımıza odaklanır. Peki ya geleceğe odaklanan bir yaklaşım mümkün olsa? Gelin, birlikte ileri beslemenin ne olduğunu ve nasıl daha etkili bir gelişim sağlayabileceğini keşfedelim.”
İleri Besleme Nedir?
İleri besleme (feedforward), geçmiş davranışları değerlendirmek yerine, gelecekte nasıl daha iyi olunabileceğine odaklanan bir gelişim yaklaşımıdır. Gelişim fırsatlarını belirlemeye, olumlu yönlendirmeye ve potansiyeli açığa çıkarmaya yardımcı olur.
Kısacası; ‘Dün ne yaptın?’ sorusundan çok, ‘Yarın nasıl daha iyi yapabilirsin?’ sorusunu sorar.”
Geri Bildirimin Ötesinde: Kavramsal Tanım
Geri bildirim, geçmişte olanları analiz ederek yapılan bir değerlendirmedir. İleri besleme ise geçmişi yargılamadan, kişinin gelecekteki davranışlarını geliştirmesi için yapılandırıcı öneriler sunar. Bu yaklaşım, kişinin savunmaya geçmeden önerileri kabul etmesini kolaylaştırır.
Geri bildirim ve ileri besleme farkları
*	Geri Bildirim; Geçmişe dönük, performans odaklıyken, İleri besleme; ileriye dönük potansiyel odaklıdır.
*	Geri Bildirim; reaktif yaklaşımı benimserken,  İleri besleme; proaktif yaklaşımı benimser.
*	Geri bildirim; yargıya dayalıyken, ileri besleme rehberlik eder
*	Geri bildirirm; mevcut duruma dair bir yansıma sunarken, ileri besleme geleceği şekillendirmeye odaklıdır.
*	Geri bildirim; performansın etkisini anlamayı sağlarken, ileri besleme performansı iyileştirmeye fırsat sunar. 
*	Geri bildirim; savunma mekanizmasını tetikleyebilirken, ileri besleme pozitif bir iletişimi destekler.
Özetle, ileri besleme sadece gelişim için değil, aynı zamanda daha pozitif, daha yapıcı bir iletişim için güçlü bir araçtır. Siz de ekibinizde bu yaklaşımı deneyerek farkı görebilirsiniz.
Neden İleri Besleme? Liderlikte ve Gelişimde Yeri
*	Liderlikte: Takım üyelerini yargılamadan yönlendirme fırsatı sunar, güven ve açıklık ortamı yaratır.
*	Gelişimde: Kişilerin potansiyeline odaklanır, davranış değişikliğini teşvik eder.
*	İletişimde: Daha açık, pozitif ve gelecek odaklı bir kültür oluşturur.
*	Performans yönetiminde: Geleneksel performans değerlendirmelerine göre daha motive edici sonuçlar doğurur.
İleri Beslemenin Temel Prensipleri
 İleri beslemenin 5 ana prensibi vardır.
1.	Geleceğe odaklanmak
2.	Çözüm odaklı yaklaşım
3.	Kişisel gelişimi destekleme
4.	Etkileşimde pozitiflik
5.	Etkin dinleme
6.	Hazırlık aşaması (hedef belirleme)dir.
Şimdi bunları detaylıca inceleyelim.
Geleceğe Odaklanmak
o	İleri beslemenin en ayırt edici özelliği, geçmiş hatalar yerine gelecekteki potansiyeli konuşmaktır. Kişinin “bundan sonra nasıl daha iyi olabilir?” sorusuna cevap arar.
Çözüm Odaklı Yaklaşım
o	Sorunları değil, çözümleri konuşur. Hatalara odaklanmak yerine, olumlu değişikliklerin nasıl yapılabileceğine dair öneriler sunar.
Kişisel Gelişimi Destekleme
o	İleri besleme, bireyin öğrenme sürecini teşvik eder. Özgüveni artırır ve gelişim yolculuğunda rehberlik sunar.
Etkileşimde Pozitiflik
o	Olumlu ve yapıcı bir dil kullanılır. Bu, hem karşı tarafın daha açık olmasını sağlar hem de ilişkiyi güçlendirir.
Etkin Dinleme
o	İleri besleme sadece konuşmak değil, aynı zamanda karşımızdakini tam olarak duymak ve anlamaktır. İhtiyaca uygun öneriler etkin dinlemeyle başlar.
Hazırlık Aşaması (Hedef Belirleme)
o	Etkili bir ileri besleme, hedefin net olduğu durumlarda mümkündür. Ne konuda gelişim istendiği veya beklenen değişimin ne olduğu önceden belirlenmelidir.

İleri Besleme Süreci Nasıl İşler?
Etkili İletişim Teknikleri
İleri beslemenin başarısı, nasıl iletişim kurulduğuna bağlıdır.
*	Açık ve anlaşılır dil kullanmak
*	Yargıdan uzak bir üslup benimsemek
*	Ben dili ile konuşmak (örneğin: "Gelecekte şunu denemeni öneririm.")
*	Empati kurmak ve karşı tarafın bakış açısını anlamak
Bu teknikler, kişinin savunmaya geçmesini engeller ve ileri beslemeyi daha alıcı hale getirir.
Uygulama Adımları
İleri besleme rastgele yapılmaz; yapıcı olması için belirli bir süreç izlenmelidir:
1.	Hazırlık: Gelişim alanı netleştirilir, hedef belirlenir.
2.	Ortam ve Zamanlama: Sessiz, dikkat dağıtıcı olmayan bir ortam seçilir.
3.	Pozitif Başlangıç: Güçlü yönler vurgulanarak başlanır.
4.	Gelişim Önerisi: Geleceğe dair açık ve uygulanabilir öneri sunulur.
5.	Karşılıklı Diyalog: Kişinin düşünceleri alınır, gerekirse öneri birlikte şekillendirilir.
6.	Teşekkür ve Teşvik: İleriye dönük olumlu beklentiyle süreç tamamlanır.
Sonuçların Takibi ve Destek
İleri besleme, yalnızca bir görüşme değil, bir gelişim sürecidir.
*	Takip etmek, önerilerin hayata geçip geçmediğini gözlemlemek açısından önemlidir.
*	Destekleyici geri dönüşler, kişinin çabasını takdir etmek ve devamını teşvik etmek için gereklidir.
*	Gerekirse ek kaynaklar, mentorluk veya tekrar besleme görüşmeleri ile sürdürülebilir gelişim sağlanabilir.
İleri Besleme Uygulama Teknikleri
* Pozitif Soru Sorma ve Yönlendirme (Beyaz Kelimeler Kullanmak)
İleri beslemenin en güçlü araçlarından biri, pozitif ve çözüm odaklı sorular sormaktır. Bu sorular, kişiyi yargılamak yerine ilham verir. Biz buna ‘beyaz kelimeler’ diyoruz.
Beyaz kelime örnekleri:
*	Gelişim
*	İyileştirme
*	Deneyim
*	Olasılık
*	Seçenek
*	Güçlü yön
*	Potansiyel
Olumsuz (siyah) kelimelerden kaçınılır:
*	Hata, yanlış, eksik, başarısız gibi kelimeler kişide savunma yaratabilir.
Pozitif soru örnekleri:
*	“Bundan sonraki projelerde nasıl daha etkili olabilirsin?”
*	“Sence bu konuda güçlü yönlerini nasıl daha iyi kullanabilirsin?”
*	“Benzer bir durumda farklı ne denemek istersin?”
Bu yaklaşım, kişinin içsel motivasyonunu harekete geçirir ve çözüm yollarını kendi bulmasını destekler.
* GROW Modeli
İleri beslemede kullanabileceğiniz bir diğer yöntem ise GROW Modeli. İleri beslemede kullanılabilecek yapılandırılmış bir koçluk tekniğidir.
GROW, dört aşamadan oluşur:
1.	G – Goal (Hedef):
Ne başarmak istiyorsun?
→ “Bu konuda ulaşmak istediğin ideal durum nedir?”
2.	R – Reality (Gerçeklik):
Şu anda neredesin?
→ “Bugünkü durumun bu hedefe ne kadar yakın?”
3.	O – Options (Seçenekler):
Hangi yolları deneyebilirsin?
→ “Alternatif olarak neler yapabilirsin?”
4.	W – Will / Way Forward (İrade / İleriye Dönük Plan):
Ne yapacaksın ve ne zaman?
→ “İlk adım olarak neyi, ne zaman yapacaksın?”
GROW modeli sayesinde ileri besleme daha yapılandırılmış, hedefe yönelik ve kişiye özel hale gelir.

İleri Beslemeyi nerelerde kullanabiliriz?
İleri besleme sadece gelişim için değil, hayatın ve işin birçok alanında kullanılabilir. Gelin, birlikte örnekleri inceleyelim: İleri beslemeyi;
•	Hedef belirlerken
•	Müşteri beklentilerini analiz ederken
•	Kariyerini şekillendirirken
•	Sunuma hazırlanırken
•	Gelişim Odaklarını belirlerken
•	Fikir geliştirirken
•	Proje Yönetiminde
•	Çatışma Yönetiminde
•	Ekip Çalışmasını iyileştirmek için
•	Takdir ederken
•	Gelişim fırsatları gördüğünde
•	Fikir paylaşıldığında
•	Bilgiyi anlamlandırmak için
•	Değişimi yönetirken
•	Stres Yönetiminde
•	Zaman Yönetiminde
•	Ekip Çalışmasını iyileştirmek için kullanabiliriz.
Gördüğünüz gibi, ileri besleme hemen her alanda hem bireysel hem de ekip performansını güçlendirecek bir araçtır.
* Doğru ve Yanlış Örneklerle Sahneleme (Geri Bildirim Senaryosu)
Sadece teorik bilgi değil, gözlem ve deneyim de çok önemlidir. Bu nedenle hem olumlu hem de olumsuz örneklerin kısa sahnelemelerle gösterilmesi, katılımcılarda farkındalık yaratır. Şimdi gelin, birlikte doğru ve yanlış örnekleri inceleyelim.”
Yanlış Örnek (Geleneksel Geri Bildirim):
“Bu sunumun birçok eksiği vardı. Daha fazla dikkat etmeliydin. Zaten geçen sefer de eksik yapmıştın.”
Sonuç: Savunma, moral bozukluğu, gelişim fırsatının kaçırılması
Doğru Örnek (İleri Besleme Yaklaşımı):
“Sunumunda veriye dayalı içeriklerin çok etkili olduğunu fark ettim. Gelecek sunumlarda bu güçlü yönünü biraz daha görsel desteklerle birleştirmeni öneririm. Sence bunu nasıl yapabilirsin?”
Sonuç: Pozitif yönlendirme, gelişim fırsatı, katılım
Video veya Canlı Sahneleme Önerisi:
“İleri besleme kavramını daha iyi anlamak için kısa sahnelemeler çok etkili olabilir.
*	2–3 dakikalık kısa roller oynayın: Örneğin, ‘Yönetici – Çalışan’ veya ‘Koç – Takım Üyesi’.
*	Bu sahneleri kaydedip daha sonra analiz edebilirsiniz.
*	Katılımcılar böylece farkı kendi gözleriyle görür ve deneyimler.”
Katılımcıların Uygulamalı Öğrenmesi İçin Senaryolar
İleri beslemeyi öğrenmenin en etkili yollarından biri deneyimleyerek uygulamaktır. Katılımcılar roller üstlenerek ileri besleme deneyimi yaşar. Hem geri bildirim veren hem de alan rolde bulunmak öğrenmeyi derinleştirir.
Uygulama Senaryoları:
Senaryo 1 – Toplantıya Hazırlıksız Gelen Takım Üyesi
“Takım üyesi toplantıya hazırlıksız geldi. Lider, cezalandırmadan nasıl yönlendirebilir?
İleri besleme yaklaşımıyla, lider önce olumlu yönleri fark eder, sonra geleceğe dönük öneriler sunar.
Örneğin: ‘Toplantıdaki katkıların çok değerli. Bir sonraki toplantıya hazırlanırken hangi adımları atabilirsin?’”
Senaryo 2 – Sunumda Gelişmiş Ama İletişimde Zorlanan Çalışan
“Bir çalışan sunumunda çok gelişmiş ama iletişim konusunda zorlanıyor. Koç, geleceğe dönük nasıl ele alır?
İleri besleme ile koç, güçlü yönleri öne çıkarır ve gelişim önerisi sunar:
‘Sunumunda veriye dayalı içeriklerin çok etkiliydi. Gelecek sunumlarda bunları görsel desteklerle daha da güçlendirebilirsin. Sen ne düşünüyorsun?’”
Senaryo 3 – Performans Görüşmesinde İleri Besleme
“Performans görüşmesinde ileri besleme yapılır. Katılımcılar bu görüşmeyi canlandırarak deneyim kazanır.
Bu sayede hem geri bildirim veren hem de alan kişi olarak ileri besleme pratiği yapılır.”
* Değerlendirme ve Grup Paylaşımı
“Her rol oyunu sonrası kısa bir grup değerlendirmesi yapmak çok önemlidir. Katılımcılara şu sorular sorulabilir:” 
*	“Hangi ifadeler etkiliydi?”
*	“Neresi daha iyi olabilir?”
*	“Duygusal etki nasıldı?”
İpucu: Katılımcılardan kendi deneyimlerini paylaşmaları istenirse öğrenme pekişir.

İleri Besleme ile Gelişim Planları Oluşturmak
* Kişisel Gelişim Hedeflerine Yön Verme
İleri besleme, bireyin güçlü yönlerine odaklanarak gelişim yolculuğunu yapılandırmasını sağlar. Belirsiz hedefler yerine, eyleme dönük ve motive edici gelişim alanları belirlenir. Kişi geçmişteki eksiklerine değil, gelecekte nasıl daha etkili olabileceğine odaklanır.
Örneğin;
1.Adım soru sormak: “Toplantılarda etki alanını genişletmek için neye ihtiyacın oluduğunu düşünüyorsun, bunun için neler yapabilirsin”
2. adım yönlendirme; “Toplantılarda daha etkili olmanı sağlamak için ön hazırlıklarını görselleştirmeni öneririm. Önümüzdeki 2 hafta bu yöntemi deneyebilirsin.”

Mentor ve Koçluk Süreçlerinde Kullanımı
İleri besleme, koçluk ve mentorluk görüşmelerinde yapılandırılmış, çözüm odaklı ve motivasyon artırıcı bir araç olarak kullanılabilir.
Koç: Danışanın kendi çözümünü keşfetmesine rehberlik eder.
Mentor: Kendi deneyimlerinden yola çıkarak geleceğe dair önerilerde bulunur.
GROW modeli bu süreçlerde etkili bir çerçeve sunar (önceki bölümle bağ kurulabilir).
İpucu: Her koçluk görüşmesinin sonunda ileri besleme niteliğinde bir "ileriye dönük öneri" paylaşılabilir.

OKR ve KPI Süreçleri ile İlişkilendirme
İleri besleme, bireysel ve ekip hedeflerinin belirlenmesinde ve izlenmesinde kritik bir katkı sağlar.
OKR (Objectives & Key Results):
İleri besleme, “başarmak istediklerine nasıl ulaşabilirsin?” sorusu etrafında kurgulanabilir.
KPI (Key Performance Indicator):
Sayısal hedeflerin ötesinde, bu hedeflere nasıl ulaşılacağı konusunda yön gösterici olur.
Örneğin;
“Müşteri memnuniyeti KPI’ını artırmak için iletişim tarzında nasıl farklılık yaratabilirsin? gibi sorular sorabilirsiniz.
Sonuç:
İleri besleme sadece bir iletişim biçimi değil, aynı zamanda kişisel ve kurumsal gelişim stratejilerine entegre edilebilecek güçlü bir yapı taşıdır.
Gelişim planları bu sayede daha motive edici, ulaşılabilir ve sürdürülebilir hale gelir.

Kurum Kültüründe İleri Beslemeyi Yaygınlaştırmak
* Yöneticiler için İleri Besleme Liderliği
İleri beslemenin kuruma yerleşmesinde en kritik rol yöneticilere düşer.
Model olurlar: İleri besleme yapan liderler, çalışanları da aynı dili konuşmaya teşvik eder.
Güvenli ortam yaratırlar: Hata korkusunun değil, gelişimin teşvik edildiği bir iklim oluştururlar.
Sürekli gelişimi desteklerler: Yalnızca değerlendirme dönemlerinde değil, günlük iletişimde ileri beslemeyi kullanırlar.
Örneğin: Yöneticilere özel “ileri besleme rehberi” veya mikro eğitim modülleri ile bu beceri sürdürülebilir hale getirilebilir.
* Takımlarda İleri Besleme Alışkanlığı Oluşturmak
İleri besleme sadece yukarıdan aşağıya değil, eş düzeyde ve aşağıdan yukarıya da çalışmalıdır. (İleri beslemeyi beklemek yerine talep edebilirsin)
Bunun için:
Takım içi kısa “ileri besleme egzersizleri” yapılabilir.
Haftalık retrospektiflerde ileri besleme dili kullanılabilir.
Başarılar kadar gelişim fırsatları da açıkça konuşulabilir.
Örneğin: Takım ritüellerine (stand-up, haftalık toplantı) kısa ileri besleme turu eklenebilir: “Bu hafta birbirimize nasıl destek olabiliriz?”
* Performans Değerlendirme Süreçlerine Entegrasyon
Klasik performans değerlendirmeleri genellikle geçmişe odaklanır ve çoğu zaman çalışanlar için stres kaynağı olabilir.
İleri besleme:
Performans görüşmelerini gelişim odaklı hale getirir.
“Geçmişte ne yaptın?” değil, “Gelecekte nasıl desteklenebilirsin?” sorusunu öne çıkarır.
Yetkinlik değerlendirmelerini yapıcı önerilere bağlar.
Örneğin: Değerlendirme formlarına “İleri besleme önerileri” bölümü eklenebilir. Bu bölümde gelişim alanlarına dair pozitif ve uygulanabilir ifadeler yer alır.
Kurumsal Dönüşüm için Hatırlatma:
İleri besleme bir beceri değil, bir yaklaşım ve kültürdür.
Kültür değişimi yukarıdan başlar ama aşağıdan beslenir.
Sürekli pratik, görünürlük ve lider desteği ile kuruma yerleşir.

Bunlara dikkat edelim 
İleri besleme, yalnızca “ne söylendiği” değil, “nasıl söylendiği” ile anlam kazanır. İşte etkili bir ileri beslemenin olmazsa olmazları:
* İçten ve Gerçek İleri Besleme: Samimi olmayan ya da yapmacık ifadeler güveni zedeler. Gözlemle desteklenen, kişiye özel ve içtenlikle sunulan besleme en değerlisidir.
* Pozitif Olmak: Eleştiri dili yerine, gelişim ve fırsat dili kullanılmalıdır. Olumlu çerçevede konuşmak hem motivasyonu hem etkileşimi artırır.
* Uygun Zaman ve Bağlamı Seçmek: İleri beslemenin etkisi, doğru zamanda ve doğru ortamda verilmesiyle artar. Kişinin açık olduğu bir an seçilmelidir; topluluk önünde değil, bire bir olmalıdır.
* Amaç Belirlemek: Ne için ileri besleme verildiği net olmalıdır. Gelişim mi hedefleniyor, yönlendirme mi? Belirsiz geri bildirimler kafa karıştırır, netlik güven yaratır.
* Düşündürmek: İleri besleme, doğrudan çözüm vermektense kişiyi düşünmeye sevk eder.
* “Bu konuda başka neyi deneyebilirsin?”
* “Bir sonraki adımda farklı ne yapabilirsin?” gibi sorular tercih edilir.
* Destekleyici Olmak: Sadece ne yapılmalı değil, nasıl destek olunacağı da önemlidir.
* “İstersen sunumuna birlikte hazırlanalım.”
* “Bu konuda kaynak önerebilirim.” gibi destek ifadeleri gelişimi kolaylaştırır.
* Çözüm Odaklı Olmak: Soruna değil, çözüme odaklanmak gerekir. Geçmişin eleştirisi yerine, geleceğin eylem planı konuşulmalıdır.
* Geleceğe Odaklı Olmak: İleri besleme, kişiyi geçmişte tutmaz. “Bundan sonra ne yapabiliriz?” sorusuyla yön verir.
* Gelişime Teşvik Etmek: İleri besleme, gelişim arzusunu tetiklemelidir. Kişinin potansiyelini görmesine ve onu kullanmasına ilham vermelidir.
Son Söz:
İleri besleme, teknik bir beceri değil; niyet, yaklaşım ve tutum bütünüdür. Bu maddelere dikkat edildiğinde, hem güven hem gelişim kültürü aynı anda desteklenmiş olur.
Unutmayın, ileri besleme sadece bir teknik değil; gelişim ve güven kültürünü inşa etmenin yoludur.” Küçük adımlar büyük farklar yaratır. Bugün öğrendiklerinizi hemen uygulamaya koyun. İleri besleme ile hem kendinizi hem ekibinizi daha güçlü ve motive bir noktaya taşıyabilirsiniz.


Eğitim Metni Sonu']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    $userMessage = trim($input['message'] ?? '');

    if ($userMessage === '') {
        echo json_encode(['error' => 'Boş mesaj gönderildi.']);
        exit;
    }

    $_SESSION['conversation'][] = ['role' => 'user', 'content' => $userMessage];

    if (!isset($_SESSION['omnichannelExplained'])) {
        $_SESSION['omnichannelExplained'] = true;
        $_SESSION['conversation'][] = ['role' => 'assistant', 'content' => 'Harika bir yaklaşım! Şimdi sana konuyu özetleyeyim:'];
    }

    $postData = [
        'model' => 'gpt-3.5-turbo',
        'messages' => $_SESSION['conversation']
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout ekledik
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(['error' => 'Curl hatası: ' . $err]);
        exit;
    }

    if ($httpCode !== 200) {
        echo json_encode(['error' => 'HTTP hatası: ' . $httpCode, 'response' => $response]);
        exit;
    }

    $responseData = json_decode($response, true);
    if (!isset($responseData['choices'][0]['message']['content'])) {
        echo json_encode(['error' => 'OpenAI cevabı eksik veya hatalı.', 'raw' => $response]);
        exit;
    }

    $aiReply = $responseData['choices'][0]['message']['content'];
    $_SESSION['conversation'][] = ['role' => 'assistant', 'content' => $aiReply];

    $finalMessage = null;
    if (!isset($_SESSION['showedNowYourTurn'])) {
        $_SESSION['showedNowYourTurn'] = true;
        $finalMessage = "Şimdi sıra sende! Bu eğitim hakkında dilediğin kadar soru sorabilir veya sonraki ekrana geçebilirsin.";
    } else {
        $finalMessage = "Eğer daha sorun varsa alabilirim ya da bir sonraki ekrana geçebilirsin.";
    }

    echo json_encode([
        'reply' => $aiReply,
        'finalMessage' => $finalMessage,
        'success' => true
    ]);
    exit;
}
?>

<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>İleri Besleme ve Geri Bildirim - AkademiMentor</title>
  <style>
    :root{
      --chat-bg:#fff;
      --chat-border:#e6e8eb;
      --chat-radius:16px;
      --chat-shadow:0 8px 24px rgba(0,0,0,.08);
      --icon-size: 100px;
      --header-h: calc(var(--icon-size) + 24px);
      --input-h:72px;
      --header-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }

    body{
      margin:0; min-height:100svh;
      background:linear-gradient(180deg,#f4f6f8 0%,#eef1f4 100%);
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
      color:#101828;
	  font-size: 210%;
    }

    .layout{
      display:grid;
      grid-template-columns: 1fr;
      grid-template-rows: 100svh;
      width:100vw; height:100svh; overflow:hidden;
    }

    .chat-area{ padding:16px; display:grid; height:100%; }

    .chat-panel{
      border:1px solid var(--chat-border); background:var(--chat-bg);
      border-radius:var(--chat-radius); box-shadow:var(--chat-shadow);
      display:grid; grid-template-rows:var(--header-h) 1fr var(--input-h);
      overflow:hidden; height:calc(100svh - 32px);
    }

    .chat-header{
      display:flex; align-items:center; justify-content:space-between;
      padding:0 16px; background: var(--header-gradient);
      color:#fff; font-weight:600;
    }
    .title-group{ display:flex; align-items:center; gap:12px; min-width:0; }
    .icon-slot{
	  width: var(--icon-size);
	  height: var(--icon-size);
	  border-radius: 12px;
	  background: rgba(255,255,255,.18);
	  display:flex; align-items:center; justify-content:center;
	  overflow:hidden; flex:0 0 var(--icon-size);
	}

	.icon-slot img, .icon-slot svg{
	  width: 100%;
	  height: 100%;
	  object-fit: contain;
	}

    .chat-messages{ overflow:auto; padding:16px; background:#fcfdff; }

    .message{max-width:70ch; padding:10px 12px; margin:8px 0;
      border-radius:12px; line-height:1.45; border:1px solid #edf0f4; background:#fff;}
    .message.user{ background:#f6f9ff; }
    .message .message-sender{ font-size:0.75em; color:#6b7280; margin-bottom:4px; }
    .message .message-bubble{ white-space:pre-wrap; word-wrap:break-word; }

    .typing-indicator{ display:none; margin:8px 0; }
    .typing-indicator.show{ display:block; }
    .typing-bubble{ display:flex; align-items:center; gap:8px; padding:8px 10px;
      border:1px solid #edf0f4; border-radius:12px; background:#fff; }
    .typing-dots{ display:flex; gap:4px; }
    .typing-dot{ width:6px; height:6px; border-radius:50%; background:#9aa4b2; animation:blink 1s infinite; }
    .typing-dot:nth-child(2){ animation-delay:.2s }
    .typing-dot:nth-child(3){ animation-delay:.4s }
    @keyframes blink{ 0%,80%,100%{opacity:.2} 40%{opacity:1} }

    .chat-input{
      border-top:1px solid var(--chat-border); background:#fff;
      display:grid; grid-template-columns:minmax(0,1fr) 112px;
      gap:8px; padding:10px;
    }
    .chat-input input,.chat-input textarea{
      box-sizing:border-box; min-width:0; width:100%; height:48px; resize:none;
      padding:10px 12px; border:1px solid #d7dde3; border-radius:12px;
      font:inherit; outline:none; background:#fff;
    }
    .send-btn{
      width:112px; height:48px; padding:0 16px;
      border:1px solid #155eef; background:#155eef; color:#fff;
      border-radius:12px; cursor:pointer; font-weight:600;
    }
  </style>
</head>
<body>
  <div class="layout">
    <main class="chat-area">
      <section class="chat-panel">
        <header class="chat-header">
          <div class="title-group">
            <div class="icon-slot">
              <img src="mentor-robot.png" alt="Logo">
            </div>
            <div class="title">İleri Besleme ve Geri Bildirim - AkademiMentor</div>
          </div>
          <div class="right-actions"></div>
        </header>

        <div class="chat-messages" id="messages">
          <div id="chatbox">
            <div class="message ai">
              <div class="message-sender">AkademiMentor</div>
              <div class="message-bubble">Sence İleri Besleme'nin Geri Bildirim'den en önemli farkı nedir kısaca yazabilir misin?</div>
            </div>
          </div>

          <div class="typing-indicator" id="typingIndicator">
            <div class="typing-bubble">
              <div class="message-sender">AkademiMentor yazıyor...</div>
              <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="chat-input">
          <input type="text" id="userInput" placeholder="Cevabınızı yazın veya soru sorun..." />
          <button class="send-btn" id="sendButton" type="button" onclick="sendMessage()">Gönder</button>
        </div>
      </section>
    </main>
  </div>

  <script>
    const chatbox = document.getElementById("chatbox");
    const input = document.getElementById("userInput");
    const sendButton = document.getElementById("sendButton");
    const typingIndicator = document.getElementById("typingIndicator");

    input.addEventListener("keypress", function(e) {
      if (e.key === "Enter") { e.preventDefault(); sendMessage(); }
    });

    function addMessage(from, text) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${from === 'Sen' ? 'user' : 'ai'}`;
      messageDiv.innerHTML = `
        <div class="message-sender">${from}</div>
        <div class="message-bubble">${text}</div>
      `;
      chatbox.appendChild(messageDiv);
      chatbox.parentElement.scrollTop = chatbox.parentElement.scrollHeight;
    }

    function showTypingIndicator() {
      typingIndicator.classList.add('show');
      chatbox.parentElement.scrollTop = chatbox.parentElement.scrollHeight;
    }
    function hideTypingIndicator() {
      typingIndicator.classList.remove('show');
    }

    function setButtonState(loading) {
      if (loading) {
        sendButton.disabled = true;
        sendButton.textContent = "Gönderiliyor...";
        input.disabled = true;
      } else {
        sendButton.disabled = false;
        sendButton.textContent = "Gönder";
        input.disabled = false;
        input.focus();
      }
    }

    async function sendMessage() {
      const userText = input.value.trim();
      if (!userText) return;

      setButtonState(true);
      addMessage("Sen", userText);
      input.value = "";

      showTypingIndicator();

      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({ message: userText })
        });

        if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }

        const data = await response.json();
        hideTypingIndicator();

        if (data.success && data.reply) {
          addMessage("AkademiMentor", data.reply.trim());
          if (data.finalMessage) {
            setTimeout(() => addMessage("AkademiMentor", data.finalMessage), 1000);
          }
        } else {
          addMessage("AkademiMentor", "Bir hata oluştu: " + (data.error || "Bilinmeyen hata"));
        }
      } catch (error) {
        console.error('Fetch error:', error);
        hideTypingIndicator();
        addMessage("AkademiMentor", "Bağlantı hatası oluştu. Lütfen tekrar deneyin.");
      } finally {
        setButtonState(false);
      }
    }

    window.addEventListener('load', () => input.focus());
  </script>
</body>
</html>