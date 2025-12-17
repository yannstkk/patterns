package src.exo2;

public class Internationale extends  Commande{

    

    public Internationale() {
        super();
    }


    void setPrice(){
        this.price = 20;
    }

   @Override
   int calculatePrice() {
    setPrice();
    return this.price;
   }

   @Override
   void pay() {
    System.out.println("le client a payee : "+this.price);
   }

    
    
}
