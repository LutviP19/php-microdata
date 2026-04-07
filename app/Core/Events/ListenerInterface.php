<?php
/**
 *  @author LutviP19 <lutvip19@gmail.com>
 * 
 * Interface ListenerInterface
 * Setiap class di folder App/Listeners WAJIB mengimplementasikan interface ini
 * agar dapat dideteksi secara otomatis oleh App::boot().
 */

namespace App\Core\Events;


interface ListenerInterface
{
    /**
     * Properti $event harus didefinisikan di dalam class implementasi.
     * Namun karena PHP Interface tidak mendukung properti, kita pastikan 
     * fungsionalitasnya ada pada method handle.
     * * @param array $data Data payload yang dikirim dari Event Dispatcher
     * @return void
     */
    public function handle(array $data);
}