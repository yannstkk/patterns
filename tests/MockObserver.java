package tests;

import src.exo1.ConcriteObserver1;

public class MockObserver extends ConcriteObserver1 {

    String msgReceived;

    public MockObserver(String name) {
        super(name);
    }

     @Override
    public void update(String msg) {
        this.msgReceived = msg;
        System.out.println(super.name + " a reçu la notification : " + msg);
    }

    public String getMsgReceived() {
        return msgReceived;
    }
    
    
}
