<?php

// namespace Tests\Feature;

use Tests\TestCase;
// use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HttpTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @return void
     * @test
     */
    public function CRUD_Complete()
    {
        /**
         *  QUERY EMPTY DATA
         */
        echo "\n\nStarting complete CRUD assert.";
        echo "\n\n  Requesting GET HTTP /share...";
        $data = $this
            ->get('shares')                                     // checks index
            ->assertStatus(200)                                 // checks page is accessible
            ->assertViewHas('shares')
                ->getOriginalContent()->getData();
        echo "\n\tIndex is accessible (200 code).";


        $this
            ->assertEquals(0, count($data['shares']));         // checks empty share data
        echo "\n\tNo share data was seen.";


        /**
         *  CREATE DATA
         */
        $shares = factory(App\Share::class, 3)->make();
        for ($i=0; $i<count($shares); $i++)
        {
            $share = $shares[$i];
            $id = $i+1;
            echo "\n\n  Creating fake data ($id)...";
            $this
                ->post('shares', $share->toArray())             // posts share data
                ->assertStatus(302);                            // checks redirect
            echo "\n\tData posted. Redirecting client.";


            $stored_share = $share;
            $stored_share->share_price = $stored_share->share_price*100;

            $this
                ->assertDatabaseHas('shares',[                  // checks database stored data
                    'share_name'  => $stored_share->share_name,
                    'share_price' => $stored_share->share_price,
                    'share_qty'   => $stored_share->share_qty,
                ]);
            echo "\n\tData was correctly stored.";

            $data = $this
                ->get("shares/$id/edit")
                ->assertStatus(200)                             // checks data is retrievable
                ->assertViewHas('share')                        //      and template receives data from model
                    ->getOriginalContent()->getData();

            foreach ($data as $value) {                         // checks editable data equals inserted
                $this->assertArrayHasKey('id', $value);
                $this->assertArrayHasKey('share_name',  $value  );
                $this->assertArrayHasKey('share_price', $value  );
                $this->assertArrayHasKey('share_qty',   $value  );
                $this->assertEquals($stored_share->share_name,  $value->share_name  );
                $this->assertEquals($stored_share->share_price, $value->share_price );
                $this->assertEquals($stored_share->share_qty,   $value->share_qty   );
            }
            echo "\n\tData was sent to view with correct values.";
        }


        /**
         *  QUERY CREATED DATA
         */
        echo "\n\n  Requesting GET HTTP /share again...";
        $data = $this
            ->get('shares')                                     // checks index
            ->getOriginalContent()->getData();
        $data_length = count($data['shares']);
        for ($index=0; $index < count($data['shares']); $index++) {
            $this->assertEquals($data['shares'][$index]->share_name,  $shares[$index]->share_name);
            $this->assertEquals($data['shares'][$index]->share_price, $shares[$index]->share_price);
            $this->assertEquals($data['shares'][$index]->share_qty,   $shares[$index]->share_qty);
        }
        echo "\n\tShare data was seen. Count: $data_length.";


        /**
         * UPDATES DATA
         */
        $new_shares = factory(App\Share::class, 3)->make();
        for ($i=0; $i<count($new_shares); $i++)
        {
            $share = $new_shares[$i];
            $id = $i+1;
            echo "\n\n  Updating fake data ($id)...";

            // print_r($shares[$i]->toArray());
            // print_r($share->toArray());
            $this
                ->assertDatabaseHas('shares',$shares[$i]->toArray());
            echo "\n\tAsserts: database contains initial data.";
            $this
                ->patch("shares/$id", $share->toArray())        // posts share data
                ->assertStatus(302);                            // checks redirect
            echo "\n\tData patched. Redirecting client.";

            $this
                ->assertDatabaseMissing('shares', $shares[$i]->toArray());
            echo "\n\tAsserts: database no more contains initial data.";

            $stored_share = $share;
            $stored_share->share_price = $stored_share->share_price*100;

            $this
                ->assertDatabaseHas('shares', $stored_share->toArray());
            echo "\n\tAsserts: database contains new data.";
        }
        // echo "\n\tData was correctly updated.";


        /**
         * DELETES DATA
         */
        echo "\n\n  Deleting data...";
        $data = $this
            ->get('shares')
            ->getOriginalContent()->getData();
        for ($index=0; $index < count($data['shares']); $index++)
        {
            $share = $data['shares'][$index];
            $id = $share->id;
            $name   = $share->share_name;
            $price  = $share->share_price;
            $qty    = $share->share_qty;

            $response = $this->delete("/shares/$id")
                ->assertStatus(302);                                // checks redirect after post delete
            echo "\n\tFake data deleted ($id).";

            $this
                ->assertDatabaseMissing('shares',$share->toArray());
            echo "\n\tDatabase misses deleted data ($id).";

        }
        $data = $this
            ->get('shares')
            ->getOriginalContent()->getData();
        $this->assertEquals(0, count($data['shares']));             // checks database length is zero
        echo "\n\tDatabase was correctly deleted and size is zero.";


    } // end function

} // end class
