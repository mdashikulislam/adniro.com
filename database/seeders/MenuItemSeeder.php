<?php

namespace Database\Seeders;

use App\Helpers\Common\NestedSetSeeder;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$this->runWithMenuId();
	}
	
	/**
	 * @param int|null $opMenuId
	 * @return void
	 */
	public function runWithMenuId(?int $opMenuId = null): void
	{
		// $appUrl = env('APP_URL');
		$appUrl = config('app.url');
		$isDemoDomain = (isDemoDomain($appUrl) || isDevEnv($appUrl));
		
		// Get menu items parameters
		$conditionsForGuest = ['guest' => 'true'];
		$conditionsForUser = ['auth' => 'true'];
		$conditionsForAuthenticUser = array_merge($conditionsForUser, ['authentic' => 'true']);
		$conditionsForImpersonating = array_merge($conditionsForUser, ['impersonating' => 'true']);
		$rolesForAdminUser = [Role::getSuperAdminRole()];
		if ($isDemoDomain) {
			$demoAdminRole = 'admin';
			$rolesForAdminUser = collect($rolesForAdminUser)->add($demoAdminRole)->toArray();
		}
		
		$logInText = [
			'en' => "Log In",
			'fr' => "Se connecter",
			'es' => "Iniciar sesión",
			'ar' => "تسجيل الدخول",
			'pt' => "Iniciar sessão",
			'de' => "Anmelden",
			'it' => "Accedi",
			'tr' => "Giriş Yap",
			'ru' => "Войти",
			'hi' => "लॉग इन करें",
			'bn' => "লগ ইন করুন",
			'zh' => "登录",
			'ja' => "ログイン",
			'th' => "เข้าสู่ระบบ",
			'ro' => "Autentificare",
			'ka' => "შესვლა",
			'he' => "התחברות"
		];
		
		$signUpText = [
			'en' => "Sign Up",
			'fr' => "S'inscrire",
			'es' => "Registrarse",
			'ar' => "إنشاء حساب",
			'pt' => "Registar",
			'de' => "Registrieren",
			'it' => "Registrati",
			'tr' => "Kayıt Ol",
			'ru' => "Зарегистрироваться",
			'hi' => "साइन अप करें",
			'bn' => "সাইন আপ করুন",
			'zh' => "注册",
			'ja' => "サインアップ",
			'th' => "สมัครสมาชิก",
			'ro' => "Înregistrare",
			'ka' => "რეგისტრაცია",
			'he' => "הרשמה"
		];
		
		$myAccountText = [
			'en' => "My Account",
			'fr' => "Mon Compte",
			'es' => "Mi Cuenta",
			'ar' => "حسابي",
			'pt' => "Minha Conta",
			'de' => "Mein Konto",
			'it' => "Il Mio Account",
			'tr' => "Hesabım",
			'ru' => "Мой Аккаунт",
			'hi' => "मेरा खाता",
			'bn' => "আমার অ্যাকাউন্ট",
			'zh' => "我的账户",
			'ja' => "マイアカウント",
			'th' => "บัญชีของฉัน",
			'ro' => "Contul Meu",
			'ka' => "ჩემი ანგარიში",
			'he' => "החשבון שלי"
		];
		
		$myListingsText = [
			'en' => "My listings",
			'fr' => "Mes annonces",
			'es' => "Mis listados",
			'ar' => "قوائمي",
			'pt' => "Minhas listagens",
			'de' => "Meine Angebote",
			'it' => "I miei annunci",
			'tr' => "Listelerim",
			'ru' => "Мои объявления",
			'hi' => "मेरी लिस्टिंग",
			'bn' => "আমার তালিকা",
			'zh' => "我的列表",
			'ja' => "マイリスト",
			'th' => "รายการของฉัน",
			'ro' => "Anunțurile mele",
			'ka' => "ჩემი სიები",
			'he' => "הרשימות שלי"
		];
		
		$favouriteListings = [
			'en' => "Favourite listings",
			'fr' => "Annonces favorites",
			'es' => "Listados favoritos",
			'ar' => "القوائم المفضلة",
			'pt' => "Listagens favoritas",
			'de' => "Favorisierte Angebote",
			'it' => "Annunci preferiti",
			'tr' => "Favori listeler",
			'ru' => "Избранные объявления",
			'hi' => "पसंदीदा लिस्टिंग",
			'bn' => "প্রিয় তালিকা",
			'zh' => "收藏列表",
			'ja' => "お気に入りリスト",
			'th' => "รายการโปรด",
			'ro' => "Anunțuri favorite",
			'ka' => "საყვარელი სიები",
			'he' => "רשימות מועדפות"
		];
		
		// Get entries by menu location
		$entriesByMenu = [
			// HEADER
			'header' => [
				[
					'type'             => 'link',
					'icon'             => 'bi-grid-fill',
					'label'            => [
						'en' => "Browse Listings",
						'fr' => "Parcourir les annonces",
						'es' => "Explorar listados",
						'ar' => "تصفح القوائم",
						'pt' => "Navegar nos anúncios",
						'de' => "Angebote durchsuchen",
						'it' => "Sfoglia gli annunci",
						'tr' => "Listeleri Gözat",
						'ru' => "Просмотр списков",
						'hi' => "लिस्टिंग ब्राउज़ करें",
						'bn' => "তালিকা ব্রাউজ করুন",
						'zh' => "浏览列表",
						'ja' => "リストを閲覧",
						'th' => "เรียกดูรายการ",
						'ro' => "Răsfoiți anunțurile",
						'ka' => "დაათვალიერეთ სიები",
						'he' => "עיין ברשימות"
					],
					'url_type'         => 'route',
					'route_name'       => 'browse.listings',
					'route_parameters' => null,
					'url'              => null,
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => null,
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => null,
					'children'         => [],
				],
				[
					'type'             => 'link',
					'icon'             => 'fa-solid fa-tags',
					'label'            => [
						'en' => "Pricing",
						'fr' => "Tarification",
						'es' => "Precios",
						'ar' => "التسعير",
						'pt' => "Preços",
						'de' => "Preisgestaltung",
						'it' => "Prezzi",
						'tr' => "Fiyatlandırma",
						'ru' => "Ценообразование",
						'hi' => "मूल्य निर्धारण",
						'bn' => "মূল্য নির্ধারণ",
						'zh' => "定价",
						'ja' => "価格設定",
						'th' => "การกำหนดราคา",
						'ro' => "Prețuri",
						'ka' => "ფასები",
						'he' => "תמחור"
					],
					'url_type'         => 'route',
					'route_name'       => 'pricing',
					'route_parameters' => null,
					'url'              => null,
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => null,
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => null,
					'children'         => [],
				],
				[
					'type'             => 'button',
					'icon'             => 'fa-solid fa-pen-to-square',
					'label'            => [
						'en' => "Create Listing",
						'fr' => "Créer une annonce",
						'es' => "Crear listado",
						'ar' => "إنشاء قائمة",
						'pt' => "Criar listagem",
						'de' => "Angebot erstellen",
						'it' => "Crea annuncio",
						'tr' => "Liste Oluştur",
						'ru' => "Создать объявление",
						'hi' => "लिस्टिंग बनाएं",
						'bn' => "তালিকা তৈরি করুন",
						'zh' => "创建列表",
						'ja' => "リストを作成",
						'th' => "สร้างรายการ",
						'ro' => "Creează anunț",
						'ka' => "შექმენით სია",
						'he' => "צור רשימה"
					],
					'url_type'         => 'route',
					'route_name'       => 'listing.create.ms.showForm',
					'route_parameters' => null,
					'url'              => null,
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => 'btn-highlight',
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => null,
					'children'         => [],
				],
				// Auth Links (for Guests)
				[
					'type'             => 'link',
					'icon'             => 'fa-solid fa-user',
					'label'            => $logInText,
					'url_type'         => 'internal',
					'route_name'       => null,
					'route_parameters' => null,
					'url'              => '#',
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => null,
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => $conditionsForGuest,
					'description'      => 'Only for guests',
					'children'         => [
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => $logInText,
							'url_type'         => 'route',
							'route_name'       => 'auth.login.showForm',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForGuest,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => $signUpText,
							'url_type'         => 'route',
							'route_name'       => 'auth.register.showForm',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForGuest,
							'children'         => [],
						],
					],
				],
				// Account Links (for Users)
				[
					'type'             => 'link',
					'icon'             => 'bi-person-circle',
					'label'            => [
						'en' => "{user.name}",
					],
					'url_type'         => 'internal',
					'route_name'       => null,
					'route_parameters' => null,
					'url'              => '#',
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => null,
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => $conditionsForUser,
					'description'      => 'Only for logged-in users',
					'children'         => [
						[
							'type'             => 'link',
							'icon'             => 'fa-solid fa-list',
							'label'            => $myListingsText,
							'url_type'         => 'route',
							'route_name'       => 'account.listings.online',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-hourglass-split',
							'label'            => [
								'en' => "Pending approval",
								'fr' => "En attente d'approbation",
								'es' => "Pendiente de aprobación",
								'ar' => "في انتظار الموافقة",
								'pt' => "Aguardando aprovação",
								'de' => "Ausstehende Genehmigung",
								'it' => "In attesa di approvazione",
								'tr' => "Onay bekliyor",
								'ru' => "Ожидает одобрения",
								'hi' => "अनुमोदन लंबित",
								'bn' => "অনুমোদন মুলতুবি",
								'zh' => "等待批准",
								'ja' => "承認待ち",
								'th' => "รอการอนุมัติ",
								'ro' => "În așteptarea aprobării",
								'ka' => "მოლოდინში დამტკიცება",
								'he' => "ממתין לאישור"
							],
							'url_type'         => 'route',
							'route_name'       => 'account.listings.pendingApproval',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-bookmarks',
							'label'            => $favouriteListings,
							'url_type'         => 'route',
							'route_name'       => 'account.savedListings',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-chat-text',
							'label'            => [
								'en' => "Messenger",
								'fr' => "Messagerie",
								'es' => "Mensajería",
								'ar' => "المراسلة",
								'pt' => "Mensageiro",
								'de' => "Nachrichtendienst",
								'it' => "Messaggistica",
								'tr' => "Mesajlaşma",
								'ru' => "Мессенджер",
								'hi' => "मैसेंजर",
								'bn' => "মেসেঞ্জার",
								'zh' => "消息",
								'ja' => "メッセンジャー",
								'th' => "การส่งข้อความ",
								'ro' => "Mesagerie",
								'ka' => "მესენჯერი",
								'he' => "מסנג'ר"
							],
							'url_type'         => 'route',
							'route_name'       => 'account.messages',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'divider',
							'icon'             => null,
							'label'            => null,
							'url_type'         => null,
							'route_name'       => null,
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => null,
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-person-lines-fill',
							'label'            => [
								'en' => "My account",
								'fr' => "Mon compte",
								'es' => "Mi cuenta",
								'ar' => "حسابي",
								'pt' => "Minha conta",
								'de' => "Mein Konto",
								'it' => "Il mio account",
								'tr' => "Hesabım",
								'ru' => "Мой аккаунт",
								'hi' => "मेरा खाता",
								'bn' => "আমার অ্যাকাউন্ট",
								'zh' => "我的账户",
								'ja' => "マイアカウント",
								'th' => "บัญชีของฉัน",
								'ro' => "Contul meu",
								'ka' => "ჩემი ანგარიში",
								'he' => "החשבון שלי"
							],
							'url_type'         => 'route',
							'route_name'       => 'account.overview',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-person-circle',
							'label'            => [
								'en' => "Profile",
								'fr' => "Profil",
								'es' => "Perfil",
								'ar' => "الملف الشخصي",
								'pt' => "Perfil",
								'de' => "Profil",
								'it' => "Profilo",
								'tr' => "Profil",
								'ru' => "Профиль",
								'hi' => "प्रोफ़ाइल",
								'bn' => "প্রোফাইল",
								'zh' => "个人资料",
								'ja' => "プロフィール",
								'th' => "โปรไฟล์",
								'ro' => "Profil",
								'ka' => "პროფილი",
								'he' => "פרופיל"
							],
							'url_type'         => 'route',
							'route_name'       => 'account.profile',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-shield-lock',
							'label'            => [
								'en' => "Security",
								'fr' => "Sécurité",
								'es' => "Seguridad",
								'ar' => "الأمان",
								'pt' => "Segurança",
								'de' => "Sicherheit",
								'it' => "Sicurezza",
								'tr' => "Güvenlik",
								'ru' => "Безопасность",
								'hi' => "सुरक्षा",
								'bn' => "নিরাপত্তা",
								'zh' => "安全",
								'ja' => "セキュリティ",
								'th' => "ความปลอดภัย",
								'ro' => "Securitate",
								'ka' => "უსაფრთხოება",
								'he' => "אבטחה"
							],
							'url_type'         => 'route',
							'route_name'       => 'account.security',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-sliders',
							'label'            => [
								'en' => "Preferences",
								'fr' => "Préférences",
								'es' => "Preferencias",
								'ar' => "التفضيلات",
								'pt' => "Preferências",
								'de' => "Einstellungen",
								'it' => "Preferenze",
								'tr' => "Tercihler",
								'ru' => "Настройки",
								'hi' => "प्राथमिकताएं",
								'bn' => "পছন্দসমূহ",
								'zh' => "偏好设置",
								'ja' => "設定",
								'th' => "การตั้งค่า",
								'ro' => "Preferințe",
								'ka' => "პარამეტრები",
								'he' => "העדפות"
							],
							'url_type'         => 'route',
							'route_name'       => 'account.preferences',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-box-arrow-right',
							'label'            => [
								'en' => "Log Out",
								'fr' => "Se déconnecter",
								'es' => "Cerrar sesión",
								'ar' => "تسجيل الخروج",
								'pt' => "Terminar sessão",
								'de' => "Abmelden",
								'it' => "Esci",
								'tr' => "Çıkış Yap",
								'ru' => "Выйти",
								'hi' => "लॉग आउट",
								'bn' => "লগ আউট",
								'zh' => "退出登录",
								'ja' => "ログアウト",
								'th' => "ออกจากระบบ",
								'ro' => "Deconectare",
								'ka' => "გამოსვლა",
								'he' => "התנתק"
							],
							'url_type'         => 'route',
							'route_name'       => 'auth.logout',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForAuthenticUser,
							'description'      => 'Only for authentic users (Not impersonated)',
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => 'bi-box-arrow-right',
							'label'            => [
								'en' => "Leave",
								'fr' => "Quitter",
								'es' => "Salir",
								'ar' => "مغادرة",
								'pt' => "Sair",
								'de' => "Verlassen",
								'it' => "Lascia",
								'tr' => "Ayrıl",
								'ru' => "Покинуть",
								'hi' => "छोड़ें",
								'bn' => "ছেড়ে দিন",
								'zh' => "离开",
								'ja' => "退出",
								'th' => "ออก",
								'ro' => "Părăsi",
								'ka' => "გასვლა",
								'he' => "עזוב"
							],
							'url_type'         => 'route',
							'route_name'       => 'impersonate.leave',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForImpersonating,
							'description'      => 'Only for impersonated users',
							'children'         => [],
						],
						[
							'type'             => 'divider',
							'icon'             => null,
							'label'            => null,
							'url_type'         => null,
							'route_name'       => null,
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForAuthenticUser,
							'roles'            => $rolesForAdminUser,
							'description'      => 'Only for admin users (with the &quot;super-admin&quot; role)',
							'children'         => null,
						],
						[
							'type'             => 'link',
							'icon'             => 'fa-solid fa-gears',
							'label'            => [
								'en' => "Admin Panel",
							],
							'url_type'         => 'route',
							'route_name'       => 'admin.panel',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForAuthenticUser,
							'roles'            => $rolesForAdminUser,
							'description'      => 'Only for admin users (with the &quot;super-admin&quot; role)',
							'children'         => [],
						],
					],
				],
			],
			
			// FOOTER
			'footer' => [
				[
					'type'             => 'title',
					'icon'             => null,
					'label'            => [
						'en' => "About Us",
						'fr' => "À propos de nous",
						'es' => "Sobre nosotros",
						'ar' => "معلومات عنا",
						'pt' => "Sobre nós",
						'de' => "Über uns",
						'it' => "Chi siamo",
						'tr' => "Hakkımızda",
						'ru' => "О нас",
						'hi' => "हमारे बारे में",
						'bn' => "আমাদের সম্পর্কে",
						'zh' => "关于我们",
						'ja' => "会社概要",
						'th' => "เกี่ยวกับเรา",
						'ro' => "Despre noi",
						'ka' => "ჩვენს შესახებ",
						'he' => "אודותינו"
					],
					'url_type'         => null,
					'route_name'       => null,
					'route_parameters' => null,
					'url'              => null,
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => null,
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => null,
					'children'         => [
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => [
								'en' => "Terms",
								'fr' => "Conditions",
								'es' => "Términos",
								'ar' => "الشروط",
								'pt' => "Termos",
								'de' => "Bedingungen",
								'it' => "Termini",
								'tr' => "Şartlar",
								'ru' => "Условия",
								'hi' => "शर्तें",
								'bn' => "শর্তাবলী",
								'zh' => "条款",
								'ja' => "利用規約",
								'th' => "ข้อกำหนด",
								'ro' => "Termeni",
								'ka' => "წესები",
								'he' => "תנאים"
							],
							'url_type'         => 'route',
							'route_name'       => 'page.terms',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => null,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => [
								'en' => "FAQ",
								'fr' => "FAQ",
								'es' => "Preguntas frecuentes",
								'ar' => "الأسئلة الشائعة",
								'pt' => "Perguntas frequentes",
								'de' => "Häufig gestellte Fragen",
								'it' => "Domande frequenti",
								'tr' => "SSS",
								'ru' => "Часто задаваемые вопросы",
								'hi' => "सामान्य प्रश्न",
								'bn' => "প্রায়শই জিজ্ঞাসিত প্রশ্ন",
								'zh' => "常见问题",
								'ja' => "よくある質問",
								'th' => "คำถามที่พบบ่อย",
								'ro' => "Întrebări frecvente",
								'ka' => "ხშირად დასმული კითხვები",
								'he' => "שאלות נפוצות"
							],
							'url_type'         => 'route',
							'route_name'       => 'page.faq',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => null,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => [
								'en' => "Anti-Scam",
								'fr' => "Anti-Arnaque",
								'es' => "Anti-Estafa",
								'ar' => "مكافحة الاحتيال",
								'pt' => "Anti-Golpe",
								'de' => "Anti-Betrug",
								'it' => "Anti-Truffa",
								'tr' => "Dolandırıcılık Karşıtı",
								'ru' => "Анти-Мошенничество",
								'hi' => "धोखाधड़ी विरोधी",
								'bn' => "স্ক্যাম বিরোধী",
								'zh' => "反诈骗",
								'ja' => "詐欺防止",
								'th' => "ป้องกันการหลอกลวง",
								'ro' => "Anti-Înșelăcie",
								'ka' => "ანტი-შენახვა",
								'he' => "אנטי-הונאה"
							],
							'url_type'         => 'route',
							'route_name'       => 'page.anti-scam',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => null,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => [
								'en' => "Privacy",
								'fr' => "Confidentialité",
								'es' => "Privacidad",
								'ar' => "الخصوصية",
								'pt' => "Privacidade",
								'de' => "Datenschutz",
								'it' => "Privacy",
								'tr' => "Gizlilik",
								'ru' => "Конфиденциальность",
								'hi' => "गोपनीयता",
								'bn' => "গোপনীয়তা",
								'zh' => "隐私",
								'ja' => "プライバシー",
								'th' => "ความเป็นส่วนตัว",
								'ro' => "Confidențialitate",
								'ka' => "კონფიდენციალურობა",
								'he' => "פרטיות"
							],
							'url_type'         => 'route',
							'route_name'       => 'page.privacy',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => null,
							'children'         => [],
						],
					],
				],
				[
					'type'             => 'title',
					'icon'             => null,
					'label'            => [
						'en' => "Contact & Sitemap",
						'fr' => "Contact & Plan du site",
						'es' => "Contacto & Mapa del sitio",
						'ar' => "اتصل بنا & خريطة الموقع",
						'pt' => "Contacto & Mapa do site",
						'de' => "Kontakt & Sitemap",
						'it' => "Contatti & Mappa del sito",
						'tr' => "İletişim & Site Haritası",
						'ru' => "Контакты & Карта сайта",
						'hi' => "संपर्क करें & साइटमैप",
						'bn' => "যোগাযোগ ও সাইটম্যাপ",
						'zh' => "联系与网站地图",
						'ja' => "お問い合わせ & サイトマップ",
						'th' => "ติดต่อ & แผนผังเว็บไซต์",
						'ro' => "Contact & Sitemap",
						'ka' => "კონტაქტი & საიტის რუკა",
						'he' => "צור קשר & מפת האתר"
					],
					'url_type'         => null,
					'route_name'       => null,
					'route_parameters' => null,
					'url'              => null,
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => null,
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => null,
					'children'         => [
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => [
								'en' => "Contact Us",
								'fr' => "Contactez-nous",
								'es' => "Contáctenos",
								'ar' => "اتصل بنا",
								'pt' => "Contacte-nos",
								'de' => "Kontaktieren Sie uns",
								'it' => "Contattaci",
								'tr' => "Bize Ulaşın",
								'ru' => "Свяжитесь с нами",
								'hi' => "हमसे संपर्क करें",
								'bn' => "আমাদের সাথে যোগাযোগ করুন",
								'zh' => "联系我们",
								'ja' => "お問い合わせ",
								'th' => "ติดต่อเรา",
								'ro' => "Contactați-ne",
								'ka' => "დაგვიკავშირდით",
								'he' => "צור קשר"
							],
							'url_type'         => 'route',
							'route_name'       => 'contact.showForm',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => null,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => [
								'en' => "Sitemap",
								'fr' => "Plan du site",
								'es' => "Mapa del sitio",
								'ar' => "خريطة الموقع",
								'pt' => "Mapa do site",
								'de' => "Sitemap",
								'it' => "Mappa del sito",
								'tr' => "Site Haritası",
								'ru' => "Карта сайта",
								'hi' => "साइटमैप",
								'bn' => "সাইটম্যাপ",
								'zh' => "网站地图",
								'ja' => "サイトマップ",
								'th' => "แผนผังเว็บไซต์",
								'ro' => "Sitemap",
								'ka' => "საიტის რუკა",
								'he' => "מפת האתר"
							],
							'url_type'         => 'route',
							'route_name'       => 'sitemap',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => null,
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => [
								'en' => "Countries",
								'fr' => "Pays",
								'es' => "Países",
								'ar' => "الدول",
								'pt' => "Países",
								'de' => "Länder",
								'it' => "Paesi",
								'tr' => "Ülkeler",
								'ru' => "Страны",
								'hi' => "देश",
								'bn' => "দেশসমূহ",
								'zh' => "国家",
								'ja' => "国",
								'th' => "ประเทศ",
								'ro' => "Țări",
								'ka' => "ქვეყნები",
								'he' => "מדינות"
							],
							'url_type'         => 'route',
							'route_name'       => 'country.list',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => null,
							'children'         => [],
						],
					],
				],
				[
					'type'             => 'title',
					'icon'             => null,
					'label'            => $myAccountText,
					'url_type'         => null,
					'route_name'       => null,
					'route_parameters' => null,
					'url'              => null,
					'target'           => null,
					'nofollow'         => 0,
					'btn_class'        => null,
					'btn_outline'      => 0,
					'css_class'        => null,
					'conditions'       => null,
					'children'         => [
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => $logInText,
							'url_type'         => 'route',
							'route_name'       => 'auth.login.showForm',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForGuest,
							'description'      => 'Only for guests',
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => $signUpText,
							'url_type'         => 'route',
							'route_name'       => 'auth.register.showForm',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForGuest,
							'description'      => 'Only for guests',
							'children'         => [],
						],
						
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => $myAccountText,
							'url_type'         => 'route',
							'route_name'       => 'account.overview',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'description'      => 'Only for logged-in users',
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => $myListingsText,
							'url_type'         => 'route',
							'route_name'       => 'account.listings.online',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'description'      => 'Only for logged-in users',
							'children'         => [],
						],
						[
							'type'             => 'link',
							'icon'             => null,
							'label'            => $favouriteListings,
							'url_type'         => 'route',
							'route_name'       => 'account.savedListings',
							'route_parameters' => null,
							'url'              => null,
							'target'           => null,
							'nofollow'         => 0,
							'btn_class'        => null,
							'btn_outline'      => 0,
							'css_class'        => null,
							'conditions'       => $conditionsForUser,
							'description'      => 'Only for logged-in users',
							'children'         => [],
						],
					],
				],
			],
		];
		
		// Get the argument passed from admin controller
		$opMenuId = castToInt($opMenuId);
		
		// Get all menus
		$menus = Menu::query()->get();
		if ($menus->count() <= 0) {
			return;
		}
		
		// Get menus locations and they ID
		// i.e. Retrieve Menu ID by the $entriesByMenu key/location
		$menuLocations = $menus->pluck('id', 'location')->toArray();
		
		// Filter the $entriesByMenu with the requested Menu ID
		if (!empty($opMenuId)) {
			$menuIdsByLocation = collect($menuLocations)->flip()->toArray();
			$menuLocationFromDb = $menuIdsByLocation[$opMenuId] ?? null;
			
			$entriesByMenu = collect($entriesByMenu)
				->filter(function ($item, $key) use ($menuLocationFromDb) {
					return empty($menuLocationFromDb) || $key === $menuLocationFromDb;
				})->toArray();
		}
		
		// ---
		
		$tableName = (new MenuItem())->getTable();
		
		// Count activated packages & payment methods (to activate/disable the 'pricing' route)
		$countPackages = Package::query()->active()->count();
		$countPaymentMethods = PaymentMethod::query()->active()->count();
		$isPricingPageReady = ($countPackages > 0 && $countPaymentMethods > 0);
		
		// Get the app's timezone
		$timezone = config('app.timezone', 'UTC');
		
		// Process entries for each menu location
		foreach ($entriesByMenu as $menuLocation => $entries) {
			$menuIdFromDb = $menuLocations[$menuLocation] ?? null;
			
			// Process all entries (including nested children) recursively
			$processedEntries = $this->processMenuItemEntries(
				entries: $entries,
				menuId: $menuIdFromDb,
				depth: 0,
				isPricingPageReady: $isPricingPageReady,
				timezone: $timezone
			);
			
			$startPosition = NestedSetSeeder::getNextRgtValue($tableName);
			NestedSetSeeder::insertEntries($tableName, $processedEntries, $startPosition);
		}
	}
	
	/**
	 * Recursively process menu-item entries and their children
	 *
	 * @param array $entries The menu-item entries to process
	 * @param int|null $menuId The menu ID to assign
	 * @param int $depth The current depth level (0 for root)
	 * @param bool $isPricingPageReady Check if the pricing page is ready or not
	 * @param string $timezone Timezone for timestamps
	 * @return array Processed entries
	 */
	private function processMenuItemEntries(array $entries, ?int $menuId, int $depth, bool $isPricingPageReady, string $timezone): array
	{
		$processedEntries = [];
		
		foreach ($entries as $key => $entry) {
			// Set menu_id for current entry
			$entry['menu_id'] = $menuId ?? $entry['menu_id'] ?? null;
			
			// Process children recursively if they exist
			$children = $entry['children'] ?? [];
			if (!empty($children) && is_array($children)) {
				$entry['children'] = $this->processMenuItemEntries(
					entries: $children,
					menuId: $entry['menu_id'],
					depth: $depth + 1,
					isPricingPageReady: $isPricingPageReady,
					timezone: $timezone
				);
			}
			
			// Set active status based on route and package count
			$active = 1;
			$routeName = $entry['route_name'] ?? null;
			if ($routeName === 'pricing') {
				$active = $isPricingPageReady ? 1 : 0;
			}
			
			// Set common entry properties
			$entry['parent_id'] = null;
			$entry['lft'] = 0;
			$entry['rgt'] = 0;
			$entry['depth'] = $depth;
			$entry['active'] = $active;
			$entry['created_at'] = now($timezone)->format('Y-m-d H:i:s');
			$entry['updated_at'] = null;
			
			$processedEntries[$key] = $entry;
		}
		
		return $processedEntries;
	}
}
