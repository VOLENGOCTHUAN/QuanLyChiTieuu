<?php
function get_usd_rate() {
    $url = "https://open.er-api.com/v6/latest/VND";

    $response = @file_get_contents($url);

    if ($response === FALSE) {
        return 25000; // fallback nếu API lỗi
    }

    $data = json_decode($response, true);

    if (isset($data["result"]) && $data["result"] == "success") {
        // API trả: 1 VND = ? USD → ta cần: 1 USD = ?
        $rate_vnd_to_usd = $data["rates"]["USD"];
        return 1 / $rate_vnd_to_usd;
    }

    return 25000; // fallback nếu API lỗi
}

function vnd_to_usd($vnd) {
    $rate = get_usd_rate();
    return $vnd / $rate;
}
?>
