<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import VueBarcodeScanner from 'vue3-barcode-scanner';
import { StreamBarcodeReader } from "vue-barcode-reader";
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref } from 'vue';
import {Html5QrcodeScanner} from "html5-qrcode";
import Quagga from '@ericblade/quagga2';


defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const form = useForm({
    qrcode: ''
});

const items = ref([]);
const isProcessing = ref(false);
const scannerActive = ref(false);
const colorMapping = {
    "COLLECT SITE": "",
    "STORE LOCAL SITE": "",
    "SHIP STAT SITE": "",
    "SHIP BATCH SITE": "",
    "ALIQUOT SITE": "",
    "RECEIVE SITE": "",
    "RECEIVE DIVISION": "",
    "SHIP DIVISION": "",
    "STORE DIVISIONAL STORAGE": "#ADD8E6",
    "RETRIEVE DIVISIONAL STORAGE": "#ADD8E6",
    "DESTRUCT DIVISIONAL STORAGE": "#ADD8E6",
    "ANALYSIS PREFERRED LAB": "#50C878",
    "ANALYSIS NON PREFERRED LAB": "#50C878",
    "ANALYSIS SITE": "#50C878",
    "ALIQUOT PREFERRED LAB": "",
    "ALIQUOT NON PREFERRED LAB": "",
    "RECEIVE SPONSOR LOCAL LAB": "",
    "ANALYSIS SPONSOR LOCAL LAB": "",
    "DEFAULT": ""
}
const onDecode = async (result) => {
    isProcessing.value = true;
    try {
        const response = await fetch('/decode/'+form.qrcode);
        const data = await response.json();
        const color = colorMapping[data[0].SampleOperationCode]
        items.value.unshift({...data[0], color});
    } catch (error) {
        const errorMessage = error.name == 'TypeError' ? 'NOT FOUND' : 'SERVER ERROR';
        items.value.unshift({
            RowNr: 1,
            SampleOperationCode: errorMessage,
            SampleId: form.qrcode,
            color: '#FFA500'
        });
    }
    form.qrcode = "";
    isProcessing.value = false;
}

const start = () => {
    scannerActive.value = true;
    const config = {
        locate: false,
        numOfWorkers: 0,
        inputStream: {
            name: "live",
            width: 640,
            height: 480,
            type: "LiveStream",
            target: document.querySelector("#videoWindow"),
            singleChannel: true
        },
        decoder: {
            readers: ["code_128_reader"],
            multiple: false
        },
        // frequency: 100,
        // locator: {
        //     halfSample: false,
        //     patchSize: "large",
        //     debug: {
        //         showCanvas: true,
        //         showPatches: true,
        //         showFoundPatches: true,
        //         showSkeleton: true,
        //         showLabels: true,
        //         showPatchLabels: true,
        //         showRemainingPatchLabels: true,
        //         boxFromPatches: {
        //             showTransformed: true,
        //             showTransformedBox: true,
        //             showBB: true
        //         }
        //     }
        // }
    };
    Quagga.init(config, err => {
        if (err) {
            console.log(err);
            return;
        }
        console.log("initialization complete");
        Quagga.start();
    });
}

const stop = () => {
    scannerActive.value = false;
    Quagga.stop();
}

Quagga.onDetected(async (data) => {
    if(isProcessing.value == true) {
        return null;
    }
    isProcessing.value = true;
    console.log(data);
    const qrcode = data[0].codeResult.code;
    try {
        console.log(qrcode);
        if ((!items.value.length && !isProcessing) || items.value[0].SampleId != qrcode) {
            console.log(qrcode)
            // const response = await fetch('/decode/' + qrcode);
            // const data_2 = await response.json();
            // const color = colorMapping[data_2[0].SampleOperationCode];
            // items.value.unshift({ ...data_2[0], color });
        }
    } catch (error) {
        items.value.unshift({
            RowNr: 1,
            SampleOperationCode: 'NOT FOUND',
            SampleId: qrcode,
            color: '#FFA500'
        });
    }
})

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <!-- <div class="py-12"> -->
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- <div class="flex justify-center pt-8 sm:justify-start sm:pt-0">
                <img src="/cerba-logo.pg" alt="Cerba Logo" class="h-16 w-auto text-gray-700 sm:h-20">
            </div> -->
            <!-- <div class="flex justify-center mt-4 sm:items-center sm:justify-between bg-white"> -->
                <!-- <div class="text-center text-sm text-gray-500 sm:text-left">
                </div> -->

                <!-- <div class="ml-4 text-center text-sm text-gray-500 sm:text-right sm:ml-0"> -->
                    <form @submit.prevent="onDecode" class="flex justify-center mt-4 p-3 sm:items-center sm:justify-between bg-white">
                         <!-- class="flex align-center justify-center item-stretch"> -->
                        <div class="mt-1 block w-full">
                            <InputLabel for="qrcode" value="Barcode" />
                            <TextInput
                                ref="qrcode"
                                id="qrcode"
                                type="text"
                                class="block w-full mt-1"
                                v-model="form.qrcode"
                                required
                            />
                        </div>
                        <!-- autocomplete="current-password" -->

                        <PrimaryButton :disabled="isProcessing" class="flex items-center justify-center block font-bold center px-8 py-3 mt-6 ml-5">
                            {{ isProcessing ? "PROCESSING" : "SEND" }}
                        </PrimaryButton>

                    </form>
                <!-- </div> -->
            <!-- </div> -->
            <div class="mt-4 bg-white dark:bg-gray-800 overflow-hidden shadow sm:rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2">
                    <div>
                        <div v-show="!scannerActive" class="flex flex-col level-item items-center">
                            <PrimaryButton @click="start" class="button my-3">Start Scanner</PrimaryButton>
                        </div>

                        <div v-show="scannerActive" class="flex flex-col level-item items-center">
                            <PrimaryButton @click="stop" class="button my-3">Stop Scanner</PrimaryButton>
                            <div id="videoWindow" class="video"></div>
                        </div>
                    </div>
                    <div>
                        <div v-for="item,index in items" class="p-1 border-t border-gray-200 dark:border-gray-700" :class="{'p-12':!index}" :style="{background: item.color}">
                            <div class="flex items-center">
                                <div class="ml-4 text-md leading-2 font-small" :class="{'font-semibold':!index, 'text-lg':!index, 'ml-8':index}">
                                    {{ item.SampleOperationCode }}
                                </div>
                            </div>

                            <div class="ml-12">
                                <div class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                                    {{ item.SampleId }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="flex justify-center mt-4 sm:items-center sm:justify-between">
                <div class="text-center text-sm text-gray-500 sm:text-left">
                </div>

                <div class="ml-4 text-center text-sm text-gray-500 sm:text-right sm:ml-0">
                    Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
                </div>
            </div>
        </div>
        <!-- </div> -->
    </AuthenticatedLayout>
</template>
