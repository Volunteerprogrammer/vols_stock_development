<?php //stdlibrary
namespace app\library;

class AppLib {
    private static $wnhlogoimg = '<img id="wnhlogo" fetchpriority="high"  width="50" height="46"  srcset="https://static.wixstatic.com/media/b6e990_3166b13408334ba2bf244dca84c4fa9e~mv2.png/v1/crop/x_77,y_56,w_790,h_810/fill/w_180,h_170,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/wnh_round_logo_col.png 1x, https://static.wixstatic.com/media/b6e990_3166b13408334ba2bf244dca84c4fa9e~mv2.png/v1/crop/x_77,y_56,w_790,h_810/fill/w_360,h_340,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/wnh_round_logo_col.png 2x" id="img_comp-l00c5rkd" src="https://static.wixstatic.com/media/b6e990_3166b13408334ba2bf244dca84c4fa9e~mv2.png/v1/crop/x_77,y_56,w_790,h_810/fill/w_180,h_170,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/wnh_round_logo_col.png" alt="wnh_round_logo_col.png" style="object-fit:cover" >';
    private static $fblogoimg = '<img id="fblogo" fetchpriority="high" width="50" height="46" srcset="https://static.wixstatic.com/media/45fb2c_9c55ed381a0148d8a29961ffbabe1e0f~mv2.png/v1/crop/x_0,y_6,w_300,h_288/fill/w_254,h_244,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/Untitled%20design%20(8).png 1x, https://static.wixstatic.com/media/45fb2c_9c55ed381a0148d8a29961ffbabe1e0f~mv2.png/v1/crop/x_0,y_6,w_300,h_288/fill/w_420,h_403,al_c,lg_1,q_85,enc_avif,quality_auto/Untitled%20design%20(8).png 2x" id="img_comp-lmrayi9l2" src="https://static.wixstatic.com/media/45fb2c_9c55ed381a0148d8a29961ffbabe1e0f~mv2.png/v1/crop/x_0,y_6,w_300,h_288/fill/w_254,h_244,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/Untitled%20design%20(8).png" alt="Untitled design (8).png" class="BI8PVQ Tj01hh" style="object-fit: cover;">';
    public static function wnhlogo() {
    	return "<div>".self::$wnhlogoimg."</div>"; 
     }
    public static function foodbanklogo() {
        return "<div>".self::$fblogoimg."</div>"; 
     }
    public static function logopair() {
        return self::$wnhlogoimg.self::$fblogoimg;
    }
    public static function agegroup($month=0,$year=0) {
        if ($month!=0 & $year!=0) {
            $today = new \DateTime();
            $dob   = new \DateTime($year."-".$month."-15");
            $diff = $dob->diff($today);
            if ($diff->y < 12) {
                $agegroup = "<12";
            } else if ($diff->y<22) {
                $agegroup = "12+";
            } else if ($diff->y<31) {
                $agegroup = "22+";
            } else if ($diff->y<40) {
                $agegroup = "31+";
            } else if ($diff->y<55) {
                $agegroup = "40+";
            } else if ($diff->y<68) {
                $agegroup = "55+";
            } else {
                $agegroup = "68+";
            }
            return $agegroup;       
        } else {
            return "";
        }
    }

}