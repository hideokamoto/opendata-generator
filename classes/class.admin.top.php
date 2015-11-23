<?php
class ODG_Admin_Top {

    public function odg_admin_menu(){
          $home_url = get_home_url();
$str = <<<EOF
        <div class="wrap">
            <h2>OpenData Generator</h2>
            <p>Now This Plugin Supports only JSON-LD.</p>
            <table class="widefat form-table">
              <thead><tr><th>　How To Use</th></tr></thead>
              <tbody><tr><td>
                <table>
                  <thead>
                  <tr><th colspan="2">JSON-LD</th></tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>See All Data</td>
                      <td><a href="{$home_url}/odg-jsonld/" target="_blank">{$home_url}/odg-jsonld/</a></td>
                    </tr>
                    <tr>
                      <td>See Single Post Data</td>
                      <td>{$home_url}/[POST_URL]/odg-jsonld/</td>
                    </tr>
                    <tr>
                      <td>Search Data for All Post</td>
                      <td><a href="{$home_url}/odg-jsonld/" target="_blank">{$home_url}/odg-jsonld/</a></td>
                    </tr>
                  </tbody>
                </table>
              </tbody>
              <thead><tr><th>　Settings</th></tr></thead>
              <tbody><tr><td>
                  Schema<hr>
                  <p>RDFの語彙PREFIXを指定するよ。<br/>デフォルトでは「http://schema.org/」が指定されてるよ。</p>
                  <br/>
                  Mapping<hr>
                  <p>カスタムフィールドとのマッピングを行うよ。<br/>ここでマッピングしないと表示されないよ</p>
              </td></tr></tbody>
            </table>
          </div>
EOF;
    echo $str;
    }
}
